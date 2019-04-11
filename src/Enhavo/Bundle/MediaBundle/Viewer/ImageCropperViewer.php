<?php
/**
 * ImageCropperViewer.php
 *
 * @since 11/04/19
 * @author gseidel
 */

namespace Enhavo\Bundle\MediaBundle\Viewer;

use Enhavo\Bundle\AppBundle\Action\ActionManager;
use Enhavo\Bundle\AppBundle\Viewer\AbstractActionViewer;
use Enhavo\Bundle\MediaBundle\Entity\Format;
use Enhavo\Bundle\MediaBundle\Media\ImageCropperManager;
use Enhavo\Bundle\MediaBundle\Media\MediaManager;
use Enhavo\Bundle\MediaBundle\Media\UrlResolver;
use FOS\RestBundle\View\View;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageCropperViewer extends AbstractActionViewer
{
    use ContainerAwareTrait;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var ImageCropperManager
     */
    private $imageCropperManager;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var UrlResolver
     */
    private $urlResolver;

    /**
     * ImageCropperViewer constructor.
     * @param ActionManager $actionManager
     * @param FlashBag $flashBag
     * @param ImageCropperManager $imageCropperManager
     * @param MediaManager $mediaManager
     * @param UrlResolver $urlResolver
     */
    public function __construct(
        ActionManager $actionManager,
        FlashBag $flashBag,
        ImageCropperManager $imageCropperManager,
        MediaManager $mediaManager,
        UrlResolver $urlResolver
    ) {
        parent::__construct($actionManager);
        $this->flashBag = $flashBag;
        $this->imageCropperManager = $imageCropperManager;
        $this->mediaManager = $mediaManager;
        $this->urlResolver = $urlResolver;
    }

    public function getType()
    {
        return 'image_cropper';
    }

    /**
     * {@inheritdoc}
     */
    public function createView($options = []): View
    {
        $view = parent::createView($options);
        $templateVars = $view->getTemplateData();
        $templateVars['data']['format'] = $this->createFormatData($options['format']);
        $view->setTemplateData($templateVars);
        return $view;
    }

    private function createFormatData(Format $format)
    {
        $url = $this->urlResolver->getPrivateUrl($format->getFile());
        $parameters = $format->getParameters();
        $ratio = $this->imageCropperManager->getFormatRatio($format);

        if(is_array($parameters)) {
            if(array_key_exists('cropHeight', $parameters) &&
                array_key_exists('cropWidth', $parameters) &&
                array_key_exists('cropX', $parameters) &&
                array_key_exists('cropY', $parameters))
            {
                return [
                    'height' => $parameters['cropHeight'],
                    'width' => $parameters['cropWidth'],
                    'x' => $parameters['cropX'],
                    'y' =>$parameters['cropY'],
                    'ratio' => $ratio,
                    'url' => $url
                ];
            }
        }

        return [
            'height' => null,
            'width' => null,
            'x' => null,
            'y' => null,
            'ratio' => $ratio,
            'url' => $url
        ];
    }

    protected function createActions($options)
    {
        $default = [
            'save' => [
                'type' => 'save'
            ],
            'move' => [
                'type' => 'event',
                'label' => 'media.image_cropper.label.tooltip_move',
                'translation_domain' => 'EnhavoMediaBundle',
                'icon' => 'zoom_out_map',
                'event' => 'image-cropper-move'
            ],
            'crop' => [
                'type' => 'event',
                'label' => 'media.image_cropper.label.tooltip_cropframe',
                'translation_domain' => 'EnhavoMediaBundle',
                'icon' => 'crop',
                'event' => 'image-cropper-crop'
            ],
            'zoom-in' => [
                'type' => 'event',
                'label' => 'media.image_cropper.label.tooltip_zoom_in',
                'translation_domain' => 'EnhavoMediaBundle',
                'icon' => 'add',
                'event' => 'image-cropper-zoom-in'
            ],
            'zoom-out' => [
                'type' => 'event',
                'label' => 'media.image_cropper.label.tooltip_zoom_out',
                'translation_domain' => 'EnhavoMediaBundle',
                'icon' => 'remove',
                'event' => 'image-cropper-zoom-out'
            ],
            'reset' => [
                'type' => 'event',
                'label' => 'media.image_cropper.label.tooltip_reset',
                'translation_domain' => 'EnhavoMediaBundle',
                'icon' => 'reply',
                'event' => 'image-cropper-reset'
            ]
        ];

        return $default;
    }

    public function configureOptions(OptionsResolver $optionsResolver)
    {
        parent::configureOptions($optionsResolver);
        $optionsResolver->setDefaults([
            'javascripts' => [
                'enhavo/image-cropper'
            ],
            'stylesheets' => [
                'enhavo/image-cropper'
            ],
            'template' => 'EnhavoMediaBundle:ImageCropper:index.html.twig',
        ]);
        $optionsResolver->setRequired('format');
    }
}
