<?php
namespace Uicms\Admin\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class AdminExtension extends Extension
{
   	public function load(array $configs, ContainerBuilder $container)
	{
		#$configValues = Yaml::parse(file_get_contents('/var/www/symfony/config/packages/ui_admin.yaml'));
	}
}