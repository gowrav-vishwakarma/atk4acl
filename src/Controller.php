<?php

namespace atk4\acl;

class Controller {
	use \atk4\core\InitializerTrait {
        init as _init;
    }

    use \atk4\core\TrackableTrait;
    use \atk4\core\AppScopeTrait;
	use \atk4\core\SessionTrait;
    use \atk4\core\NameTrait;
    use \atk4\core\ContainerTrait;
    

    /**
     * Initialize controller.
     */
    public function init()
    {
        $this->_init();

    }


}