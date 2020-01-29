<?php

namespace atk4\acl\Controller;
use atk4\core\DIContainerTrait;

class Acl extends \atk4\acl\Controller {

	use DIContainerTrait;

	public $model; // To put condition on model
	public $role; // Based on which post auth model user belongs to
	public $view; // add acl button for super user and create actions based on ACL

	public $acl_type = null;
	public $available_options = ['All', 'None'];
	public $auth_model = null; // Can be post_id or anything if Acl is applied in Department->Post->Employee System
	public $auth_model_role_field = null; // Can be post_id or anything if Acl is applied in Department->Post->Employee System

	public $acl_model_class = null;
	public $role_class = null;
	public $status_field = 'status';

	public $action_allowed = null; // Final Array determines action allowed
	public $status_actions = []; // Final Array determines action allowed on each status
	public $permissive_acl = 'All';

	public $db = null;

	function init() {
		parent::init();

		if (!$this->auth_model) {
			$this->auth_model = $this->app->auth->model;
		}
		if (!$this->db) {
			$this->db = $this->app->db;
		}

		$this->auth_model_role_field = $this->app->getConfig('acl/model_role_field', 'role_id');
		$this->role_class = $this->app->getConfig('acl/role_class', '\atk4\acl\Model\Role');

		$this->model = $this->getModel();
		$this->view = $this->getView();
		// Actual Acl between role and acl_type
		$this->role = $this->auth_model[$this->auth_model_role_field];
		$this->acl_type = isset($this->model->acl_type) ? $this->model->acl_type : $this->model->getModelCaption();

		$this->status_field = $this->app->getConfig('acl/status_field', 'status');

		$this->acl_model_class = $this->app->getConfig('acl/model_class', '\atk4\acl\Model\Acl');
		$this->acl_model = new $this->acl_model_class($this->db);

		$this->acl_model->addCondition('acl_type', $this->acl_type);
		$this->acl_model->addCondition('role_id', $this->role);
		$this->acl_model->tryLoadAny();

		if ($this->model->hasField($this->app->getConfig('acl/created_by_field', 'created_by_id'))) {
			$this->available_options = array_merge($this->available_options, ['SelfOnly']);
		}

		if (isset($this->model->assigned_field)) {
			$this->available_options = array_merge($this->available_options, ['Assigned To Self']);
		}

		$this->model_vp1 = $modal_vp1 = $this->app->add(['Modal', 'title' => 'Role Selection']);
		$modal_vp1->set(\Closure::fromCallable([$this, 'manageAclPage']));

		$this->modal_vp2 = $this->app->add(['Modal', 'title' => 'ACL For Selected Role']);
		$this->modal_vp2->set(\Closure::fromCallable([$this, 'manageAclForm']));

		if ($this->isSuperUser()) {
			$acl_btn = $this->view->menu->addItem(['ACL', 'icon' => 'ban']);
			$acl_btn->on('click', $modal_vp1->show());
		}

		$this->canDo();
		$this->set_status_actions();

		// Apply Condition on Model
		$this->applyConditionOnModel();

		// Apply Actions on View
		$this->addActionsInView();
	}

	protected function applyConditionOnModel() {
		$a = [];
		$status_included = [];
		$where_condition = [];

		foreach ($this->status_actions as $status => $acl) {
			// acl can be true(for all, false for none and [] for employee created_by_ids)
			$acl = $acl['view'];
			$display = $acl['display'];
			$acl = $acl[0];

			if ($status === 0) {
				// No status in model, just actions, so focus on view condition without status specific
				if ($acl === 'All') {
					break;
				}
				// No Conditions to put
				if ($acl === 'None') {
					$acl = -1;
				}
				// put some un matchable condition on appropirate field
				elseif ($acl === 'SelfOnly') {
					$acl = $this->auth_model->id;
				}

				$this->model->addCondition($this->getConditionalField($status, 'view'), $acl);
				break; // Must not be further set present like $actions=[['view','delete'],['c','d']], Without status C/D has no value
			} else {
				// We have various status based actions defined

				if ($acl === "All") {
					$status_included = [$status];
					$where_condition[] = "([status] = '$status')";
					continue;
				}
				if ($acl === 'None') {
					// $where_condition[] = "([status] <> '$status')";
					continue;
				}
				if ($acl === "SelfOnly" || $acl === "Assigned To Self") {
					$status_included = [$status];
					$where_condition[] = "( ([" . strtolower($status) . "] in (" . $this->auth_model->id . ")) AND ([status] = \"$status\") )";
				}
			}
		}

		// BUG-Solution : If all status view is set NONE, ALL SHOWS ;)
		if (empty($status_included)) {
			$where_condition[] = "(false)";
		}

		if (!empty($where_condition)) {
			$q = $this->model->dsql();

			$filler_values = ['status' => $this->model->getElement($this->status_field)];
			foreach ($this->action_allowed as $status => $actions) {
				$filler_values[strtolower($status)] = $this->model->getElement($this->getConditionalField($status, 'view'));
			}

			$this->model->addCondition(
				$q->expr("(" . implode(" OR ", $where_condition) . ")",
					$filler_values
				)
			)
			;

		}
	}

	protected function addActionsInView() {
		// currently managed for Table, GRID and ACL/ But planned to work on any custom view also

		if (($this->view instanceof \atk4\ui\Table) || ($this->view instanceof \atk4\ui\Grid)) {
			$status_field = $this->app->getConfig('acl/status_field', 'status');
			if ($this->model->hasField($status_field)) {
				$this->view->addDecorator($status_field, $this->view->add(new \atk4\acl\TableColumn\AclActionDecorator($this->status_actions, $this)));
			} else {
				$this->view->addColumn($status_field, $this->view->add(new \atk4\acl\TableColumn\AclActionDecorator($this->status_actions, $this)));
			}
			if ($this->view->canCreate) {
				$this->view->canCreate = $this->acl_model['can_add'] === null ? ($this->permissive_acl == "All" ? true : false) : $this->acl_model['can_add'];
			}
		}
	}

	protected function manageAclPage($p) {
		$form = $p->add('Form', ['buttonSave' => ['Button', 'update', 'primary']]);
		$g = $form->addGroup(['width' => 'two']);
		$role_class_obj = new $this->role_class($this->db);
		$g->addField('role', [
			'Lookup',
			'model' => $role_class_obj,
			'hint' => 'Lookup field is just like AutoComplete, supports all the same options.',
			'placeholder' => 'Search for roles',
			'search' => [$role_class_obj->title_field],
		]);
		$g->addField('entity', ['disabled' => true])->set($this->acl_type);

		$form->onSubmit(function ($form) {
			return [$this->modal_vp2->show(['role' => $form->model['role']])];
		});
	}

	protected function manageAclForm($p) {

		// Load Existing acl_model for given role/and type
		$acl_m = new \atk4\acl\Model\Acl($this->db);
		$acl_m->addCondition('acl_type', $this->acl_type);
		$acl_m->addCondition('role_id', $this->app->stickyGet('role'));
		$acl_m->tryLoadAny();
		$acl_array = json_decode($acl_m['acl'], true);

		$form = $p->add('Form');
		$form->addField('allow_add', ['CheckBox'])->set($acl_m['can_add'] ? true : false);

		foreach ($this->model->actions as $status => $actions) {
			$grp = $form->addGroup($status);
			foreach ($actions as $act) {
				$grp->add(['View'])->set($act);
				$grp->addField($status . '_' . $act, ['DropDown'], ['enum' => $this->available_options])
					->set(isset($acl_array[$status][$act]) ? $acl_array[$status][$act] : ($this->permissive_acl ? "All" : "None"));
			}
		}

		$form->onSubmit(function ($form) use ($acl_m) {
			$acl_array = [];
			foreach ($this->model->actions as $status => $actions) {
				$acl_array[$status] = [];
				foreach ($actions as $act) {
					$acl_array[$status][$act] = $form->model[$status . '_' . $act];
				}
			}

			$acl_m['can_add'] = $form->model['allow_add'];
			$acl_m['acl'] = json_encode($acl_array);
			$acl_m->save();

			return $form->success("ACL is all set, logout and login from this role to see effects");
		});
	}

	protected function canDo() {
		$this->action_allowed_raw = json_decode($this->acl_model['acl'], true);

		$saved_action_allowed = json_decode($this->acl_model['acl'], true);

		if (!isset($this->model->actions)) {
			$this->model->actions = [['view', 'edit', 'delete']];
		}

		foreach ($this->model->actions as $status => $actions) {
			foreach ($actions as $action) {
				$acl_value = isset($saved_action_allowed[$status][$action]) ? $saved_action_allowed[$status][$action] : $this->permissive_acl;
				$this->action_allowed[$status][$action] = ($this->isSuperUser() && $this->app->getConfig('acl/all_rights_to_superuser', 'true') == 'true') ? 'All' : $acl_value;
			}
		}
	}

	public function isSuperUser() {
		return in_array(
			$this->auth_model[str_replace("_id", '', $this->auth_model_role_field)],
			$this->app->getConfig('acl/SuperUserRoles', ['SuperUser', 'SuperAdmin'])
		);
	}

	protected function set_status_actions() {
		foreach ($this->action_allowed as $status => $actions) {
			$this->status_actions[$status] = [];
			foreach ($actions as $act => $permission) {
				$display = (in_array($act, ['view', 'edit', 'delete'])) ? false : ($this->model->hasMethod('page_' . $act) ? 'page' : 'method');
				$this->status_actions[$status][$act] = [$permission, 'display' => $display];
			}
		}
	}

	public function getConditionalField($status, $action) {
		if (!$this->isAssignField($status, $action)) {
			return 'created_by_id';
		}

		return isset($this->model->assigned_field) ? $this->model->assigned_field : $this->app->getConfig('acl/assigned_field', 'assigned_to_id');
	}

	public function isAssignField($status, $action) {
		return (strpos(strtolower($this->action_allowed[$status][$action][0]), 'assign') !== false);
	}

	function getModel() {
		return $this->owner instanceof \atk4\data\Model ? $this->owner : $this->owner->model;
	}

	function getView() {
		return $this->owner instanceof \atk4\data\Model ? $this->owner->owner : $this->owner;
	}

}