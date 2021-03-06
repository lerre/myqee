<?php

/**
 * MyQEE ORM 处理资源类型字段DI控制器
 *
 * @author     呼吸二氧化碳 <jonwang@myqee.com>
 * @category   Module
 * @package    ORM
 * @copyright  Copyright (c) 2008-2016 myqee.com
 * @license    http://www.myqee.com/license.html
 */
class OOP_ORM_DI_Resource extends OOP_ORM_DI
{
    protected $url;

    public function check_config()
    {
        if (is_string($this->config))
        {
            if (preg_match('#^(xml|json|http|https)://(.*)$#', $this->config, $m))
            {
                switch ($m[1])
                {
                    case 'xml':
                    case 'json':
                        $this->config = array
                        (
                            'type'     => $m[1],
                            'resource' => 'http://'. $m[2],
                        );
                        break;

                    case 'http':
                    case 'https':
                    default:
                        $this->config = array
                        (
                            'type'     => 'json',
                            'resource' => $m[1] .'://'. $m[2],
                        );
                        break;
                }
            }
        }

        parent::check_config();
    }

    public function format_config()
    {
        if (isset($this->config['json']))
        {
            $this->config['resource'] = $this->config['json'];
            $this->config['type']     = 'json';
        }
        elseif(isset($this->config['xml']))
        {
            $this->config['resource'] = $this->config['xml'];
            $this->config['type']     = 'xml';
        }

        if (!isset($this->config['format']) || !$this->config['format'])
        {
            if ($this->config['type']=='json')
            {
                $this->config['format'][] = 'json';
            }
            elseif ($this->config['type']=='xml')
            {
                $this->config['format'][] = 'xml';
            }
        }

        if (isset($this->config['api']))
        {
            $this->config['resource'] = $this->config['api'];
            unset($this->config['api']);
        }
    }


    /**
     * 获取当前类型的数据
     *
     * @param OOP_ORM_Data $obj
     * @param $data
     * @param $compiled_data
     * @return mixed
     */
    public function & get_data(OOP_ORM_Data $obj, & $data, & $compiled_data, & $compiled_raw_data)
    {
        # 处理URL变量
        $this->url = $this->config['resource'];
        if (preg_match_all('#{{([a-z0-9_]+)}}#i', $this->url, $m))foreach($m[1] as $v)
        {
            $this->url = str_replace('{{'. $v .'}}', $obj->$v, $this->url);
        }

        # 获取缓存
        if (isset($this->config['cache']))
        {
            /**
             * @var $cache Cache
             */
            list($cache, $key) = $this->get_cache_instance_and_key($obj);

            $tmp_data = $cache->get($key);
        }
        else
        {
            $tmp_data = null;
        }

        # 获取内容
        if (null === $tmp_data || false === $tmp_data)
        {
            $tmp_data = HttpClient::factory()->get($this->url)->data();
            if (false === $tmp_data)return false;

            # 处理数据类型
            if (isset($this->config['field_type']))
            {
                OOP_ORM_DI::_check_field_type($this->config['field_type'], $tmp_data);
            }

            # 设置缓存
            if (isset($this->config['cache']))
            {
                $cache->set($key, $tmp_data, isset($this->config['cache']['expired'])?$this->config['cache']['expired']:3600, isset($this->config['cache']['expire_type'])?$this->config['cache']['expire_type']:null);
            }
        }

        # 处理格式化数据
        if (isset($this->config['format']))
        {
            OOP_ORM_DI::_do_de_format_data($this->config['format'], $tmp_data);
        }

        $compiled_data[$this->key]     = $tmp_data;
        $compiled_raw_data[$this->key] = $tmp_data;

        return $compiled_data[$this->key];
    }


    /**
     * 设置数据
     *
     * @param OOP_ORM_Data $obj
     * @param $data
     * @param $compiled_data
     * @param $new_value
     * @param bool $has_compiled
     * @return bool
     */
    public function set_data(OOP_ORM_Data $obj, & $data, & $compiled_data, & $compiled_raw_data, $new_value, $has_compiled)
    {
        return false;
    }

    /**
     * 返回用于生成cache的key的option
     *
     * @param OOP_ORM_Data $obj
     * @return array
     */
    protected function cache_key_option($obj)
    {
        return array
        (
            'url' => $this->url,
        );
    }
}