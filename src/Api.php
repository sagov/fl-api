<?php

namespace FL\Api;

use FL\Config\Config;
use FL\Log\ConfigLog;
use FL\Log\Logger;
use FL\User\Context;
use FL\User\User;


class Api
{


    public static function run()
    {

        // установка лога - логирование ошибок и иссключений
        $config = Config::getInstance();
        $config_api = $config->get('fl-api');
        $config_api_log = $config_api->get('log')->get('error');
        $configlog = new ConfigLog($config_api_log);
        if ($configlog->isEnabled()){
            $writers = $configlog->getWriters();

            if (count($writers)>0){
                $logger = new Logger();
                foreach ($writers as $writer){
                    $logger->addWriter($writer);
                }
                Logger::registerExceptionHandler($logger);
                Logger::registerErrorHandler($logger);
                Logger::registerFatalErrorShutdownFunction($logger);
            }
        }

        $request = new \OAuth2\Request();
        $authorization = $request->headers('AUTHORIZATION');
        $access_token = trim(str_replace('Bearer ','',$authorization));
        $user_context = new Context\Oauth2();
        $user_context->Auth($access_token);
        User::getInstance($user_context);
    }


    public static function isAllowed($aclconfig)
    {
        $flag = true;
        if (!empty($aclconfig['permission'])){
            $user = User::getInstance();
            $flag = $user->isAllowed($aclconfig['permission']);
        }
        return $flag;
    }

    public function isAuth($config)
    {
        return (isset($config['auth'])) ? $config['auth'] : true;

    }

    public static function isValidParams(array $params,array $config, &$detail = []){
        if (isset($config['params'])){
            foreach ($config['params'] as $name_param=>$val_param){
                if(!isset($params[$name_param]) and isset($val_param['allowNull']) and !$val_param['allowNull']) {
                    break;
                }
                if (isset($val_param['validator'])){
                    foreach ($val_param['validator'] as $validator){
                        $valid_options = (isset($validator['options'])) ? $validator['options'] : null;
                        if(!isset($validator['name'])){
                            break;
                        }
                        $valid = new $validator['name']($valid_options);
                        if (!$valid->isValid($params[$name_param])){
                            $detail['validator'] = $validator['name'];
                            $detail['param'] = $name_param;
                            $detail['value'] = $params[$name_param];
                            $detail['messages'] = $valid->getMessages();
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }




}