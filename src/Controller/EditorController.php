<?php
namespace Uicms\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Uicms\Admin\Form\Type\UIFormType;
use Uicms\App\Service\Model;
use Uicms\App\Service\Nav;
use Uicms\App\Service\Paginator;
use Uicms\App\Service\Params;
use Uicms\App\Service\Security;
use Uicms\App\Service\Viewnav;
use Uicms\App\Service\UIFile;

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
        $total = $model->count($params);
        if($params['offset'] > $total) {
            $params['offset'] = 0;
        }
        $results = $model->getAll($params);
        $paginator = new Paginator($params['offset'], $params['limit'], $total);
        
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

    public function form($page, $entity_name, Params $params_service, Model $common_model, Request $request, Nav $nav, Viewnav $viewnav, TranslatorInterface $translator, $id=null, $next_step='')
    {
        # Init
        $params = $params_service->get($page['slug'], $request);
        $ui_config = $this->getParameter('ui_config');
        $form_config = $ui_config['entity'][$entity_name]['form'];
        if(isset($params['linked_to']) && $params['linked_to']) {
            $model_linked = $common_model->get($params['linked_to'])->mode('admin');
            $params['linked_to_row'] = $model_linked->getRowById($params['linked_to_id']);
            $params['linked_to_slug'] = $model_linked->getSlug();
        }
        $model = $common_model->get($entity_name)->mode('admin');
        

        #
        # Get or create row
        #
        if (!$id || (!$row = $model->getRowById($id))) {
            $row = $model->new($this->getUser());
            $parent = (int)$params['dir'] ? $model->getRowById($params['dir']) : null;
        } else {
            $parent = $row->getParent();
        }
        $current = clone $row;
        

        #
        # Tab & nav
        #
        $params['path'] = $model->getPath($row->getParent() ? $row->getParent()->getId() : 0, true);
        $view_nav = $viewnav->get($entity_name, $row, array_merge($params, array('dir'=>$row->getParent() ? $row->getParent()->getId() : 0)));
        if($row->getId()) {
            $nav->setCurrentTabAttribute('title', $row->_name);
        }
        $current_tab = $nav->getCurrentTab();
        
        
        #
        # Links
        #
        if($row->getId()) {
            $foreign_keys = $model->getForeignKeys();
            $linkables_entities = $model->getLinkablesEntities();
            $row->_total_linked = 0;
            $links = [];
            $children = [];
            $links_forms = [];

            # Preview
            if(isset($page['public_route']) && $page['public_route'] && isset($ui_config['preview_key']) && $ui_config['preview_key']) {
                $page['public_route'] .= '?preview_key=' . $ui_config['preview_key'];
            }
            
            /*if(isset($ui_config['entity'][$entity_name]['preview_route']) && ($preview_route = $ui_config['entity'][$entity_name]['preview_route'])) {
                $route_vars = [];
                $pattern = '/^\{([A-Za-z_]+)\.([A-Za-z_]+)\}$/';

                foreach($ui_config['entity'][$entity_name]['preview_vars'] as $route_var=>$var) {
                    if(preg_match($pattern, $var, $preg)) {
                        $method_name = lcfirst(str_replace('_', '', ucwords($preg[2], '_')));
                        $eval_expression = sprintf('$%s->get%s()', $preg[1], $method_name);
                        eval("\$route_vars[\$route_var] = $eval_expression;");
                    } else {
                        $route_vars[$route_var] = $var;
                    }
                }

                $preview_url = $this->generateUrl($preview_route, $route_vars, UrlGeneratorInterface::ABSOLUTE_URL) . "?preview_key=" . $ui_config['preview_key'];
            }*/


            #
            # N->N Links
            #            
            foreach($linkables_entities as $i=>$linked_entity) {
                $link_entity = $model->getLinkEntity(array($linked_entity->getName(), $entity_name));
                $linked_rows = [];

                # Display
                $page_slug = '';
                foreach($ui_config['admin']['pages'] as $j=>$page_config) {
                    if(isset($page_config['arguments']['entity_name']) && $page_config['arguments']['entity_name'] == $linked_entity->getName() && (!isset($page_config['display']) or $page_config['display'])) {
                        $page_slug = $page_config['slug'];
                    }
                }

                if($page_slug) {
                    $linked_rows = $linked_entity->mode('admin')->getAll(array('linked_to'=>$entity_name, 'linked_to_id'=>$row->getId()));

                    $links[$linked_entity->getName()] = array(
                                            'name'=>$linked_entity->getName(),
                                            'link_table_name'=>$link_entity->getName(),
                                            'page_slug'=>$page_slug,
                                            'rows'=>$linked_rows,
                                        );

                    if($linked_rows) {
                        $row->_total_linked++;
                    }
                }
 
                # Form
                if(isset($ui_config['entity'][$link_entity->getName()]['form']) && ($link_form_config = $ui_config['entity'][$link_entity->getName()]['form']) && $linked_rows) {
                    
                    $table_name = $ui_config['entity'][$model->getName()]['table_name'];
                    $linked_table_name = $ui_config['entity'][$linked_entity->getName()]['table_name'];

                    foreach($linked_rows as $linked_row) {
                        $link_row = $link_entity->getRow(['findby'=>[$table_name=>$row->getId(), $linked_table_name=>$linked_row->getId()]]);
                        $link_form = $this->get('form.factory')->createNamed('ui_form_link_' . $linked_table_name  . '_' . $linked_row->getId(), UIFormType::class, $link_row, array('ui_config'=>$ui_config, 'form_config'=>$link_form_config, 'translator'=>$translator, 'model'=>$common_model));
                        $link_form->handleRequest($request);
                        if ($link_form->isSubmitted() && $link_form->isValid()) {
                            $link_entity->persist($link_form->getData());
                            $link_entity->flush();
                        }
                        $linked_row->_form = $link_form->createView();
                    }
                }
            }
            
            #
            # 1->N Links displaying (Children)
            #
            foreach($foreign_keys as $i=>$foreign_key) {
                
                # Page slug
                foreach($ui_config['admin']['pages'] as $j=>$page_config) {
                    if(isset($page_config['arguments']['entity_name']) && $page_config['arguments']['entity_name'] == $foreign_key['entity']->getName() && (!isset($page_config['display']) or $page_config['display'])) {
                        $foreign_key['page_slug'] = $page_config['slug'];
                    }
                }
                
                # Add to results
                if(isset($foreign_key['page_slug']) && $foreign_key['page_slug']) {
                    $foreign_key['rows'] = $foreign_key['entity']->mode('admin')->getAll(array('limit'=>20,'findby'=>array($foreign_key['db_name'] => $row->getId())));
                    $children[] = $foreign_key;
                }
            }

            # Results
            $row->_links = $links;
            $row->_children = $children;
        }






        #
        # Create form
        #
        $form = $this->createForm(UIFormType::class, $row, array('ui_config'=>$ui_config, 'form_config'=>$form_config, 'parent'=>$parent, 'translator'=>$translator, 'model'=>$common_model));

        # Handle request data & redirect
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            try {
                $id = $model->persist($form->getData());
                $model->flush();
                $row = $model->getRowById($id);

                # Event OnPersist (repository)
                if(method_exists($model, 'onPersist')) {
                    $model->onPersist($row);
                }

                # File
                foreach($form_config['fields'] as $field_name=>$field_config) {
                    $field_method = 'get' . $field_config['name'];

                    if($field_config['type'] == 'UIFileType' && $row->$field_method()) {
                        $file = new UIFile($ui_config);
                        
                        # Rotation
                        $rotation = $form->get('Rotation' . $field_config['name'])->getData();
                        $file->rotate($row->$field_method(), $rotation);
                        
                        # Delete file
                        if($delete = $form->get('Delete' . $field_config['name'])->getData()) {
                            $file->delete($row->$field_method());
                            $set_method = 'set' . $field_config['name'];
                            $row->$set_method('');
                        }
                        
                    }
                }
            } catch (\Throwable $throwable) {
                $this->addFlash('error', $throwable->getMessage());
            }

            if(isset($current_tab['parent']) && $current_tab['parent']) {
                $model->link([$row->getId()], $current_tab['parent']['route']['params']['entity_name'], [$current_tab['parent']['route']['params']['id']]);
                $nav->removeTab($current_tab['route']['id']);
                return $this->redirectToRoute($current_tab['parent']['route']['name'], $current_tab['parent']['route']['params']);
            } else {
                switch($next_step) {
                    case 'new':
                        return $this->redirectToRoute('admin_page_action', array('slug'=>$request->get('slug'), 'action'=>'form'  ));
                        break;
                    
                    case 'next':
                        if($view_nav['next'] && $view_nav['next']->getId()) {
                            return $this->redirectToRoute('admin_page_action_id', array('slug'=>$request->get('slug'), 'action'=>'form', 'id'=>$view_nav['next']->getId()));
                        } else {
                            return $this->redirectToRoute('admin_page_action', array('slug'=>$request->get('slug'), 'action'=>'index'));
                        }
                        break;
                    
                    default:
                        return $this->redirectToRoute('admin_page_action_id', array('slug'=>$request->get('slug'), 'action'=>'form', 'id'=>$id));
                }
                
            }
        }





        # Render
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
                'preview_url'=>isset($preview_url) ? $preview_url : '',
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