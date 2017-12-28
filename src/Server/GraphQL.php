<?php

namespace FL\Api\Server;

use FL\Api\Application;
use FL\Api\ServerAbstract;
use FL\Config\Config;
use FL\Api\Api;
use FL\Api\Response\Type as ApiType;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\GraphQL as LibGraphQL;
use GraphQL\Type\Schema;


class GraphQL extends ServerAbstract
{



    public function handle($request = false)
    {
        // Получение запроса
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $query = $input['query'];

        Api::run();


        $queryType = $this->_getSchemaType($query);


        
        // Создание схемы
        $schema = new Schema([
            'query' => $queryType,
        ]);



        $myErrorFormatter = function(Error $error) {
            return FormattedError::createFromException($error);
        };

        $myErrorHandler = function(array $errors, callable $formatter) {
            return array_map($formatter, $errors);
        };

        // Выполнение запроса
        $result = LibGraphQL::executeQuery($schema, $query, null, null)
            ->setErrorFormatter($myErrorFormatter)
            ->setErrorsHandler($myErrorHandler)
            ->toArray();

        if ($request) {
            return $result;
        } else {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($result);
        }
    }

    protected function _getType($name)
    {

        switch (strtolower($name)) {
            case ApiType::STRING:
                return Type::string();
                break;

            case ApiType::BOOLEAN:
                return Type::boolean();
                break;

            case ApiType::INTEGER:
                return Type::int();
                break;

            case ApiType::FLOAT:
                return Type::float();
                break;

            default:
                return Type::string();
                break;
        }
    }

    protected function _getObjectType($name, array $fields)
    {
        $result = [];
        foreach ($fields['fields'] as $key => $field) {
            $result[$key] = [];
            if (isset($field['fields']) and is_array($field['fields']) and count($field['fields']) > 0) {
                $result[$key]['type'] = $this->_getObjectType($key, $field);
            } else {
                $type = (isset($field['type'])) ? $field['type'] : null;
                if ($type == ApiType::COLLECTION) {
                    $result[$key]['type'] = Type::listOf($this->_getType(ApiType::STRING));
                } else {
                    $result[$key]['type'] = $this->_getType($type);
                }
            }
        }

        if (isset($fields['return']) and $fields['return'] == ApiType::COLLECTION) {
            return Type::listOf(new ObjectType([
                'name' => $name,
                'fields' => $result,
                'resolve' => function ($root, $args, $c, ResolveInfo $info) {
                    return $info->getFieldSelection();
                }
            ]));
        } else {
            return new ObjectType([
                'name' => $name,
                'fields' => $result,
                'resolve' => function ($root, $args, $c, ResolveInfo $info) {
                    return $info->getFieldSelection();
                }
            ]);
        }
    }

    protected function _getSelection($selections)
    {
        $result = [];
        foreach ($selections as $selection) {
            $result[$selection->name->value] = null;
            if (count($selection->selectionSet->selections) > 0) {
                $result[$selection->name->value] = $this->_getSelection($selection->selectionSet->selections);
            }
        }
        return $result;
    }

    protected function _toArray($string)
    {
        $documentNode = \GraphQL\Language\Parser::parse(new \GraphQL\Language\Source($string ?: '', 'GraphQL'));
        $definitions = $documentNode->definitions;

        $result = array();
        foreach ($definitions as $definition) {
            $result[$definition->operation] = $this->_getSelection($definition->selectionSet->selections);
        }
        return $result;
    }


    protected function _getSchemaType($query)
    {
        $res_arr = $this->_toArray($query);




        $config = Config::getInstance();
        $config_api = $config->get('fl-api');
        $config_apimodule = $config_api->get('module');

        $namespace = $config_apimodule->get('namespace');
        $module_path = $config_apimodule->get('path');

        $afields_query = [];
        foreach ($res_arr['query'] as $name_service => $row) {
            $akey = explode('_', $name_service);

            // установка модуля
            $modulename = ucwords(strtolower($akey[0]));

            $appConfig = [
                'name' => $modulename,
                'namespace' => $namespace,
                'path' => $module_path,
            ];
            $module = new Application($appConfig);
            $module_config = $module->getConfig();

            if (isset($module_config['acl'])) {
                if (!Api::isAllowed($module_config['acl'])) {
                    throw new \Exception('API module ' . $modulename . ' access denied');
                    return;
                }
            }


            $module_class = $namespace . '\\' . str_replace(' ', '\\',
                    ucwords(str_replace('_', ' ', $name_service)));
            $config_module_class = [];
            if (isset($module_config['src'][$module_class])) {
                $config_module_class = $module_config['src'][$module_class];
            }

            if (!class_exists($module_class)) {
                throw new \Exception($name_service . ' not found');
                return;
            }


            if (isset($module_config['src'][$module_class]['acl'])) {
                if (!Api::isAllowed($module_config['src'][$module_class]['acl'])) {
                    throw new \Exception('API service ' . $module_class . ' access denied');
                    return;
                }
            }

            // создание объекта класса
            $class = new $module_class();

            $afieldsmethods = [];
            foreach ($row as $name_method => $row_method) {

                if (!method_exists($class, $name_method)) {
                    throw new \Exception('Method ' . $name_method . ' not found');
                    return;
                }

                if (isset($module_config['src'][$module_class]['methods'][$name_method]['acl'])) {
                    if (!Api::isAllowed($module_config['src'][$module_class]['methods'][$name_method]['acl'])) {
                        throw new \Exception('Access denied API method ' . $name_method);
                        return;
                    }
                }



                $config_method = [];
                if (isset($config_module_class['methods'][$name_method])) {
                    $config_method = $config_module_class['methods'][$name_method];
                }

                $args = [];
                if (isset($config_method['params'])) {
                    foreach ($config_method['params'] as $name_param => $val_param) {
                        if ($val_param['type'] == ApiType::OBJECT) {
                            $args[$name_param] = Type::listOf($this->_getType($val_param['type']));
                        } else {
                            $args[$name_param] = $this->_getType($val_param['type']);
                        }
                    }
                }

                if (isset($config_method['fields']) and is_array($config_method['fields']) and count($config_method['fields']) > 0) {
                    $afields = [];
                    foreach ($config_method['fields'] as $field_name => $field_value) {
                        $afields[$field_name] = [];
                        if (isset($field_value['fields']) and is_array($field_value['fields']) and count($field_value['fields']) > 0) {
                            $afields[$field_name]['type'] = $this->_getObjectType($field_name, $field_value);
                        } else {
                            $afields[$field_name]['type'] = $this->_getType((isset($field_value['type'])) ? $field_value['type'] : null);
                        }
                    }

                    if (isset($config_method['return']) and $config_method['return'] == ApiType::COLLECTION) {
                        $type_method = Type::listOf(new ObjectType([
                            'name' => 'fields',
                            'fields' => $afields
                        ]));
                    } else {
                        $type_method = new ObjectType([
                            'name' => 'fields',
                            'fields' => $afields
                        ]);
                    }
                } else {
                    if ($config_method['return'] == ApiType::COLLECTION) {
                        $type_method = Type::listOf($this->_getType($config_method['return']));
                    } else {
                        $type_method = $this->_getType($config_method['return']);
                    }
                }

                $afieldsmethods[$name_method] = [
                    'type' => $type_method,
                    'resolve' => function ($root, $args, $context, ResolveInfo $info) use ($class, $name_method, $module_class, $module) {

                        $cache_response = $module->getCacheResponse($module_class, $name_method, $args);
                        $response_result = $cache_response->getResult();
                        if ($cache_response->is() and isset($response_result)) {
                            return $response_result;
                        }
                        $module_config = $module->getConfig();

                        if (!Api::isValidParams($args, $module_config['src'][$module_class]['methods'][$name_method], $detail)) {
                            throw new Error(implode(', ', $detail['messages']));
                            return;
                        }
                        try {
                            $result = call_user_func_array(array($class, $name_method), $args);
                        }catch (\Exception $e){
                            throw new Error($e->getMessage(),$e->getCode());
                            return;
                        }

                        if ($cache_response->is()) {
                            $cache_response->save($result);
                        }
                        return $result;
                    }
                ];

                if (count($args) > 0) {
                    $afieldsmethods[$name_method]['args'] = $args;
                }
                if (!empty($config_method['description'])) {
                    $afieldsmethods[$name_method]['description'] = $config_method['description'];
                }


            }
            $afields_query[$name_service] = [
                'type' => new ObjectType([
                    'name' => 'method_'.$name_service,
                    'fields' => $afieldsmethods,
                    'description' => 'Methods ' . $name_service,
                    'resolve' => function ($root, $args, $c, ResolveInfo $info) {
                        return $info->getFieldSelection();
                    }
                ]),
                'description' => (isset($config_module_class['name'])) ? $config_module_class['name'] : 'API ' . $name_service,
                'resolve' => function ($root, $args, $c, ResolveInfo $info) {
                    return $info->getFieldSelection();
                },

            ];
        }
        // Содание типа данных "Запрос"
        $result = new ObjectType([
            'name' => 'Query',
            'fields' => $afields_query
        ]);
        return $result;
    }

}