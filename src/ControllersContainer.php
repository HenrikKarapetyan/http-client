<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/8/11  6:22:0.
 */

/**
 * Created by PhpStorm.
 * User: Henrik
 * Date: 4/10/2018
 * Time: 9:35 PM
 */

namespace henrik\http_client;
use sparrow\container\Container;
use sparrow\di\Instantiator;
use sparrow\core\Application;

class ControllersContainer extends Container
{
    /**
     * @param $controllers
     * @throws \Exception
     * @throws \sparrow\container\exceptions\IdAlreadyExistsException
     * @throws \sparrow\container\exceptions\ServiceNotFoundException
     * @throws \sparrow\container\exceptions\TypeException
     * @throws \sparrow\di\exceptions\ServiceConfigurationException
     * @throws \sparrow\di\exceptions\ServiceNotFoundException
     */
    public function __construct()
    {
        $common_controllers = require_once Application::$sparrow->root_dir."/common".
                                        DIRECTORY_SEPARATOR."controllers.php";

        $profile_controllers_file =  Application::$sparrow->getConfigPath().DIRECTORY_SEPARATOR."controllers.php";
        if (file_exists($profile_controllers_file)){
            $common_controllers = array_merge_recursive($common_controllers, require_once $profile_controllers_file);
        }
        
        $this->addControllers($common_controllers);
    }

    public function addControllers($controllers) {

         foreach ($controllers as $key => $value) {
            $obj = Instantiator::instantiate($value);
            $this->set($key, $obj);
        }
    }


}