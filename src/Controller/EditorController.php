<?php
namespace Uicms\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Uicms\Admin\Form\Type\UIFormType;
use Uicms\App\Service\Model;
use Uicms\App\Service\Nav;
use Uicms\App\Service\Paginator;
use Uicms\App\Service\Params;
use Uicms\App\Service\Security;
use Uicms\App\Service\Viewnav;

class EditorController extends AbstractController
{
    public function index($page, $entity_name, Params $params_service, Model $model, Request $request, Nav $nav)
    {
        $params = $params_service->get($page['slug'], $request);
        $ui_config = $this->getParameter('ui_config');
        
        # Linked to
        if(isset($params['linked_to']) && $params['linked_to']) {
            $model_linked = $model->get($params['linked_to'])->mode('admin');
            $params['linked_to_row'] = $model_linked->getRowById($params['linked_to_id']);
            foreach($ui_config['admin']['pages'] as $page) {
                if(isset($page['arguments']['entity_name']) && $page['arguments']['entity_name'] == $params['linked_to']) {
                     $params['linked_to_slug'] = $page['slug'];
                }
            }
        }

        # Misc
        $model = $model->get($entity_name)->mode('admin');
        $params['path'] = $model->getPath($params['dir'], true);
        $current_tab = $nav->getCurrentTab();
        $nav->setCurrentTabAttribute('title', ucfirst($current_tab['route']['slug']));
        
        # Data
        $results = $model->getAll($params);
        $paginator = new Paginator($params['offset'], $params['limit'], $model->count($params));
        
        return $this->render(
                'admin/tpl/editor/index.html.twig',
                [
                     'page'=>$page,
                     'nav'=>$nav,
                     'params'=>$params,
                     'model'=>$model, 
                     'rows'=>$results, 
                     'paginator'=>$paginator,
                ]
            );
    }

    public function form($page, $entity_name, $id=null, Params $params_service, Model $model, Request $request, Nav $nav, Viewnav $viewnav)
    {
        # Init
        $params = $params_service->get($page['slug'], $request);
        $ui_config = $this->getParameter('ui_config');
        $form_config = $ui_config['entity'][$entity_name]['form'];
        if(isset($params['linked_to']) && $params['linked_to']) {
            $model_linked = $model->get($params['linked_to'])->mode('admin');
            $params['linked_to_row'] = $model_linked->getRowById($params['linked_to_id']);
            $params['linked_to_slug'] = $model_linked->getSlug();
        }
        $model = $model->get($entity_name)->mode('admin');
        
        # Get current row from db or create new if no id provided
        if (!$id || (!$row = $model->getRowById($id))) {
            $row = $model->new($this->getUser());
        }
        $current = clone $row;

        # Tab & nav
        $params['path'] = $model->getPath($row->getParent() ? $row->getParent()->getId() : 0, true);
        $view_nav = $viewnav->get($entity_name, $row, array_merge($params, array('dir'=>$row->getParent() ? $row->getParent()->getId() : 0)));
        $nav->setCurrentTabAttribute('title', $row->_name);
        $current_tab = $nav->getCurrentTab();

        # Create form
        $form = $this->createForm(UIFormType::class, $row, array('ui_config'=>$ui_config, 'form_config'=>$form_config));
        
        # Handle request data & redirect
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData()->setParent($model->find($params['dir']));
            
            try {
                $id = $model->persist($data, $current);
            } catch (\Throwable $throwable) {
                $this->addFlash('error', $throwable->getMessage());
            }
            
            if(isset($current_tab['parent']) && $current_tab['parent']) {
                $model->link(array($id), $current_tab['parent']['route']['params']['entity_name'], array($current_tab['parent']['route']['params']['id']));
                $nav->removeTab($current_tab['route']['id']);
                return $this->redirectToRoute($current_tab['parent']['route']['name'], $current_tab['parent']['route']['params']);
            } else {
                return $this->redirectToRoute('admin_page_action_id', array('slug'=>$request->get('slug'), 'action'=>'form', 'id'=>$id));
            }
        }
        
        # Links
        if($row->getId()) {
            # Linkables entities
            $linkables_entities = $model->getLinkablesEntities();
            $row->_total_linked = 0;
            foreach($linkables_entities as $i=>$entity) {
                # Slug of the CMS page of the linked Entity
                $page_slug = '';
                foreach($ui_config['admin']['pages'] as $j=>$page_config) {
                    if(isset($page_config['arguments']['entity_name']) && $page_config['arguments']['entity_name'] == $entity->getName()) {
                        $page_slug = $page_config['slug'];
                    }
                }
                # Add link to row
                $link_entity = $model->getLinkEntity(array($entity->getName(), $entity_name));
                $linked_rows = $entity->getAll(array('linked_to'=>$entity_name, 'linked_to_id'=>$row->getId()));
                $row->_links[$entity->getName()] = array(
                                        'name'=>$entity->getName(),
                                        'link_table_name'=>$link_entity->getName(),
                                        'page_slug'=>$page_slug,
                                        'rows'=>$linked_rows,
                                    );
                if($linked_rows) {
                    $row->_total_linked++;
                }
            }
        }
        
        # Render form
        return $this->render(
            'admin/tpl/editor/form.html.twig',
            [
                'page'=>$page,
                'params'=>$params,
                'nav'=>$nav,
                'form'=>$form->createView(), 
                'model'=>$model, 
                'row'=>$row,
                'view_nav'=>$view_nav,
            ]
        );
	}

    public function select($page, $entity_name, Params $params_service, Model $model, Request $request, Nav $nav)
    {
        $model = $model->get($entity_name)->mode('admin');
        $params = $params_service->get($page['slug'], $request);
        $params['path'] = $model->getPath($params['dir'], true);
        $rows = $model->getAll($params);
        $nav->setCurrentTabAttribute('title', 'Sélectionner');

        return $this->render(
            'admin/tpl/editor/select.html.twig',
            [
                'page'=>$page, 
                 'nav'=>$nav,
                 'params'=>$params,
                 'model'=>$model, 
                 'rows'=>$rows, 
                 'paginator'=>new Paginator($params['offset'], $params['limit'], $model->count($params))
            ]
        );
    }
}