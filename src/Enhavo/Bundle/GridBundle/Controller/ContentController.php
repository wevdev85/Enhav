<?php
/**
 * ContentController.php
 *
 * @since 23/08/14
 * @author Gerhard Seidel <gseidel.message@googlemail.com>
 */

namespace Enhavo\Bundle\GridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Enhavo\Bundle\GridBundle\Item\ItemFormType;

class ContentController extends Controller
{
    public function itemAction(Request $request)
    {
        $formName = $request->get('formName');
        $type = $request->get('type');

        $formFactory = $this->container->get('form.factory');

        $resolver = $this->container->get('enhavo_grid.item_type_resolver');
        /** @var $formType ItemFormType */
        $formType = $resolver->getFormType($type);
        $formType->setFormName($formName);
        $form = $formFactory->create($formType, null, array(
            'csrf_protection' => false,
        ));
        $label = $resolver->getLabel($type);

        return $this->render('EnhavoGridBundle:Form:form.html.twig', array(
            'formItem' => $form->createView(),
            'formName' => $formName,
            'formOrder' => 0,
            'formType' => $type,
            'label' => $label
        ));
    }
} 
