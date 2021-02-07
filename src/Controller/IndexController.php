<?php
namespace Uicms\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

use Uicms\App\Service\Model;
use Uicms\App\Service\Nav;

class IndexController extends AbstractController
{
    public function index(Request $request, Nav $nav, $slug='', $action='', $id=0)
    {
        # Version strategy
        $version = 'v3.1';
		$this->get('session')->set('theme_path', new PathPackage('themes/admin', new StaticVersionStrategy($version)));
		$this->get('session')->set('js_path', new PathPackage('js', new StaticVersionStrategy($version)));
        
        # UI Config & browser params
		$ui_config = $this->getParameter('ui_config');
        $this->get('session')->set('ui_config', $ui_config);
        $url_parameters = array_merge($request->query->all(), $request->request->all());
        
		# Locale
		$this->get('session')->set('locale', $this->getParameter('locale'));
		$request->setLocale($this->getParameter('locale'));
        
		# Slug
		if(!$slug && ($keys = array_keys($ui_config['admin']['pages']))) {
			$slug = $keys[0];
		} else if(!$slug) {
			throw $this->createNotFoundException('No slug!');
		}

		# Get page related to slug
		if(isset($ui_config['admin']['pages'][$slug])) {
			$page = $ui_config['admin']['pages'][$slug];
		} else {
			throw $this->createNotFoundException('No data found for page '.$slug);
		}
		
        # Current action
        if(!$action && !isset($url_parameters['action'])) {
            $action = isset($page['action']) && $page['action'] ? $page['action'] : 'index';
        } else if(isset($url_parameters['action'])) {
            $action = $url_parameters['action'];
        }
        
        # Nav
        if($slug != 'action') {

            # Add current route to history
            $new_route = array( 'name'=>$request->attributes->get('_route'),
                                'action'=>$action,
                                'params'=>$request->attributes->get('_route_params'),
                                'url'=>$this->generateUrl($request->attributes->get('_route'), $request->attributes->get('_route_params')),
                                'slug'=>$slug,  
                        );
            $new_route['id'] = $nav->generateRouteId($new_route);
            $nav->addRoute($new_route);
            
            # Add tab if does not exist
            $parent_tab = isset($url_parameters['parent']) ? $nav->getTab($url_parameters['parent']) : array();
            if($parent_tab) {
                $ui_config = $this->getParameter('ui_config');
                foreach($ui_config['admin']['pages'] as $admin_page) {
                    if($admin_page['slug'] == $parent_tab['route']['params']['slug']) {
                         $parent_tab['route']['params']['entity_name'] = $admin_page['arguments']['entity_name'];
                    }
                }
            }
            
            if(!$current_tab = $nav->getTab($new_route['id'])) {            
                $new_tab = array(   'name'=>$new_route['name'],
                                    'route'=>$new_route,
                                    'title'=>$new_route['name'],
                                    'parent'=>$parent_tab,
                                );

                if(($prev_route = $nav->getPreviousRoute()) && (!isset($url_parameters['target']) || $url_parameters['target'] != 'blank')) {
                    $nav->addTabAtPosition($new_tab, $nav->getTabPosition($prev_route['id']));
                } else {
                    $nav->addTab($new_tab);
                }
            } else if(isset($url_parameters['parent'])) {
                $current_tab['parent'] = $parent_tab;
                $nav->updateTab($current_tab);
            }
        }
        
        # Attributes
        $attributes = array_merge(isset($page['arguments']) ? $page['arguments'] : array(), $request->attributes->all(), $url_parameters, array('page'=>$page));
        
        # Forward to the correct controller
        return $this->forward($page['controller'] . "Controller::" . $action, $attributes);
	}    
}