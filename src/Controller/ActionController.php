<?php
namespace Uicms\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;

use Uicms\App\Service\Model;
use Uicms\App\Service\Nav;
use Uicms\App\Service\Params;
use Uicms\Admin\Form\DataTransformer\FileTransformer;

class ActionController extends AbstractController
{
    public function delete($entity_name, $selection, Model $model, Nav $nav)
    {
        try {
            $model->get($entity_name)->delete($selection);
        } catch(\Throwable $throwable) {
            $this->addFlash('error', 'delete_error');
        }
        
       $redirect = $nav->getCurrentTab();
       return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
	}
    
    public function move($entity_name, $selection, $target, Model $model, Nav $nav)
    {
        try {
			$model->get($entity_name)->move($selection, $target);
        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'move_error');
		}
        
        $redirect = $nav->getCurrentTab();
		return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
	}
    
    public function newfolder($entity_name, $new_folder_name, Model $model, Nav $nav, Request $request, Params $params_service)
    {
        try {
            $model = $model->get($entity_name);
            $params = $params_service->get($model->getSlug(), $request);
			$row = $model->new($this->getUser());
            $method = $model->method($model->config['name_field'], 'set');
            $row->setIsDir(1);
            $row->$method($new_folder_name);
            if(isset($params['dir']) && (int)$params['dir']) {
                $parent = $model->getRowById($params['dir']);
                $row->setParent($parent);
            }
            $model->persist($row);

        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'folder_error');
        }
        
        $redirect = $nav->getCurrentTab();
		return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
	}
    
    public function publish($entity_name, $selection, Model $model, Nav $nav)
    {
        try {
            $model->get($entity_name)->mode('admin')->publish($selection);
        } catch(\Throwable $throwable) {
            $this->addFlash('error', 'publish_error');
        }
        
       $redirect = $nav->getCurrentTab();
       return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
    }
    
    public function conceal($entity_name, $selection, Model $model, Nav $nav)
    {
		try {
			$model->get($entity_name)->mode('admin')->conceal($selection);
        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'conceal_error');
		}
        
       $redirect = $nav->getCurrentTab();
       return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
    }
    
    public function duplicate($entity_name, $selection, Model $model, Nav $nav)
    {
        try {
            $model->get($entity_name)->mode('admin')->duplicate($selection);
        } catch(\Throwable $throwable) {
            $this->addFlash('error', 'duplicate_error');
        }
        
        $redirect = $nav->getCurrentTab();
        return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
    }
    
    public function position($entity_name, $selection, $position, Model $model, Nav $nav, Request $request, Params $params_service)
    {
        $ui_config = $this->getParameter('ui_config');
        $params = $params_service->get($model->get($entity_name)->getSlug(), $request);
        try {
            $model->get($entity_name)->position($selection, $position, $params);
        } catch(\Throwable $throwable) {
            $this->addFlash('error', 'position_error');
        }

        if(!$request->isXmlHttpRequest()) {
            $redirect = $nav->getCurrentTab();
            return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
        } else {
            $params_service->unset($model->get($entity_name)->getSlug(), 'linked_to', $request);
            return new JsonResponse(['message' => 'positions_set'], Response::HTTP_OK);
        }
    }
    
    public function upload($entity_name, Model $model, Nav $nav, Request $request, Params $params_service)
    {
        $current_tab = $nav->getCurrentTab();
        $params = $params_service->get($current_tab['route']['slug'], $request);
        
        $ui_config = $this->getParameter('ui_config');
        $form_config = $ui_config['entity'][$entity_name]['form'];
        $model = $model->get($entity_name)->mode('admin');
        
        try {
            if ($file = $request->files->get('file')) {
                $file_field = $model->getField(array('type'=>'UIFileType'));
                $field_name_method = 'set' . $file_field['name'];
                $transformer = new FileTransformer($file_field, $ui_config);
                $new_file_name = $transformer->reverseTransform($file);
                $new = $model->new($this->getUser());
                $new->$field_name_method($new_file_name);
                $new->setName($new_file_name);
                $new->setParent($model->find($params['dir']));
                $new_id = $model->persist($new);
            }
        } catch(\Throwable $throwable) {
            $this->addFlash('error', 'upload_error');
        }

        if(!$request->isXmlHttpRequest()) {
            $redirect = $nav->getCurrentTab();
            return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
        } else {
            return new JsonResponse(['message' => $new_id], Response::HTTP_OK);
        }
    }
    
    public function link($entity_name, $selection, $action_entity_name, $action_selection, Model $model, Nav $nav)
    {
		try {
			$model->get($entity_name)->link($selection, $action_entity_name, $action_selection);
        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'link_error');
		}
        
        $current_tab = $nav->getCurrentTab();
        $nav->removeTab($current_tab['route']['id']);
        $redirect = $nav->getTab($current_tab['parent']['route']['id']);
		return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
	}
    
    public function unlink($entity_name, $selection, $action_entity_name, $action_selection, Model $model, Nav $nav)
    {
		try {
			$model->get($entity_name)->unlink($selection, $action_entity_name, $action_selection);
        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'unlink_error');
		}
        
        $redirect = $nav->getCurrentTab();    
		return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
	}
    
    public function removetab($route_id, Model $model, Nav $nav)
    {
		try {
            $tab = $nav->getTab($route_id);
			$nav->removeTab($route_id);
        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'tab_not_found');
		}
        
        if($tab && isset($tab['route'])) {
            $redirect = $nav->getLastTab($tab['route']['slug']);      
    		return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
        } else {
            return $this->redirectToRoute('admin');
        }
        
    }
    
    public function canceltab($route_id, Model $model, Nav $nav)
    {
		try {
            $tab = $nav->getTab($route_id);
			$nav->removeTab($route_id);
        } catch(\Throwable $throwable) {
			$this->addFlash('error', 'tab_not_found');
		}

        $redirect = $nav->getTab($tab['parent']['route']['id']);      
		return $this->redirectToRoute($redirect['route']['name'], $redirect['route']['params']);
    }
}