<?php namespace Cubic\ImageResizer;

use System\Classes\PluginBase;
use Cubic\ImageResizer\Classes\Image;
use Validator;

/**
 * ImageResizer Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'cubic.imageresizer::lang.plugin.name',
            'description' => 'cubic.imageresizer::lang.plugin.description',
            'author'      => 'Cubic Ltd',
            'icon'        => 'icon-picture-o',
            'homepage'    => 'https://github.com/cubicltd/wn-imageresizer-plugin',
            'replaces' => [
                'ToughDeveloper.ImageResizer' => '<1.5.0'
            ],
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'cubic.imageresizer.access_settings' => [
                'tab'   => 'cubic.imageresizer::lang.permission.tab',
                'label' => 'cubic.imageresizer::lang.permission.label'
            ]
        ];
    }

    public function boot(){
        Validator::extend('valid_tinypng_key', function($attribute, $value, $parameters) {
            try {
                \Tinify\setKey($value);
                \Tinify\validate();
            } catch(\Tinify\Exception $e) {
                return false;
            }

            return true;
        });
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'resize' => function($file_path, $width = false, $height = false, $options = []) {
                    $image = new Image($file_path);
                    return $image->resize($width, $height, $options);
                },
                'imageWidth' => function($image) {
                    if (!$image instanceOf Image) {
                        $image = new Image($image);
                    }
                    return getimagesize($image->getCachedImagePath())[0];
                },
                'imageHeight' => function($image) {
                    if (!$image instanceOf Image) {
                        $image = new Image($image);
                    }
                    return getimagesize($image->getCachedImagePath())[1];
                }
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'cubic.imageresizer::lang.settings.label',
                'icon'        => 'icon-picture-o',
                'description' => 'cubic.imageresizer::lang.settings.description',
                'class'       => 'Cubic\ImageResizer\Models\Settings',
                'order'       => 0,
                'permissions' => ['cubic.imageresizer.access_settings']
            ]
        ];
    }

    public function registerListColumnTypes()
    {
        return [
            'thumb' => [$this, 'evalThumbListColumn'],
        ];
    }

    public function evalThumbListColumn($value, $column, $record)
    {
        $config = $column->config;

        // Get config options with defaults
        $width = isset($config['width']) ? $config['width'] : 50;
        $height = isset($config['height']) ? $config['height'] : 50;
        $options = isset($config['options']) ? $config['options'] : [];

        // attachMany relation?
        if (isset($record->attachMany[$column->columnName]))
        {
            $file = $value->first();
        }
        // attachOne relation?
        else if (isset($record->attachOne[$column->columnName]))
        {
            $file = $value;
        }
        // Mediafinder
        else
        {
            $file = storage_path() . '/app/media' . $value;
        }

        $image = new Image($file);
        return $image->resize($width, $height, $options)->render();
    }
}
