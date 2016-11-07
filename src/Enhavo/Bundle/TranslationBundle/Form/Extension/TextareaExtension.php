<?php
/**
 * TextareaExtension.php
 *
 * @since 03/11/16
 * @author gseidel
 */

namespace Enhavo\Bundle\TranslationBundle\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextareaExtension extends TranslationExtension
{
    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return TextareaType::class;
    }
}