<?php
/**
 * CreateViewer.php
 *
 * @since 29/05/15
 * @author gseidel
 */

namespace Enhavo\Bundle\AppBundle\Viewer\Viewer;

use Enhavo\Bundle\AppBundle\Exception\TypeNotFoundException;
use Enhavo\Bundle\AppBundle\Viewer\AbstractViewer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateViewer extends AbstractViewer
{
    public function getTabs()
    {
        $tabs = $this->optionAccessor->get('tabs');
        return $tabs;
    }

    public function getDefaultTab()
    {
        return [
            $this->metadata->getHumanizedName() => [
                'label' => sprintf('%s.label.%s', $this->metadata->getHumanizedName(), $this->metadata->getHumanizedName()),
                'template' => 'EnhavoAppBundle:Tab:default.html.twig'
            ]
        ];
    }

    public function getButtons()
    {
        $buttons = $this->optionAccessor->get('buttons');

        foreach($buttons as $button) {
            if(!array_key_exists('type', $button)) {
                throw new TypeNotFoundException(sprintf('button type is not defined'));
            }
        }

        return $buttons;
    }

    public function getFormTemplate()
    {
        return $this->optionAccessor->get('form.template');
    }

    public function getFormAction()
    {
        $action = $this->optionAccessor->get('form.action');
        return $this->container->get('router')->generate($action);
    }

    public function getActionRoute()
    {
        return sprintf(
            '%s_%s_create',
            $this->metadata->getApplicationName(),
            $this->metadata->getHumanizedName()
        );
    }

    public function getDefaultFormTemplate()
    {
        return 'EnhavoAppBundle:View:tab.html.twig';
    }

    public function getType()
    {
        return 'create';
    }

    public function createView()
    {
        $view = parent::createView();
        $view->setTemplate($this->configuration->getTemplate('EnhavoAppBundle:Resource:create.html.twig'));
        $view->setTemplateData(array_merge($view->getTemplateData(), [
            'buttons' => $this->getButtons(),
            'form' => $this->getForm()->createView(),
            'tabs' => $this->getTabs(),
            'form_template' => $this->getFormTemplate(),
            'form_action' => $this->getFormAction(),
            'data' => $this->resource
        ]));
        return $view;
    }


    public function configureOptions(OptionsResolver $optionsResolver)
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'buttons' => [
                'cancel' => [
                    'type' => 'cancel',
                ],
                'save' => [
                    'type' => 'save',
                ]
            ],
            'form' => [
                'template' => $this->getDefaultFormTemplate(),
                'action' => $this->getActionRoute()
            ],
            'tabs' => $this->getDefaultTab()
        ]);
    }
}