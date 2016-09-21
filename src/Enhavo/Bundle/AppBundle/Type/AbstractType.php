<?php
/**
 * Created by PhpStorm.
 * User: jhelbing
 * Date: 02.02.16
 * Time: 13:48
 */

namespace Enhavo\Bundle\AppBundle\Type;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Enhavo\Bundle\AppBundle\Exception\PropertyNotExistsException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractType implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Return the value the given property and object.
     *
     * @param $resource
     * @param $property
     * @return mixed
     * @throws PropertyNotExistsException
     */
    protected function getProperty($resource, $property)
    {
        $method = sprintf('get%s', ucfirst($property));
        if(method_exists($resource, $method)) {
            return call_user_func(array($resource, $method));
        }
        throw new PropertyNotExistsException(sprintf(
            'Trying to call "%s" on class "%s", but method does not exists. Maybe you spelled it wrong or you didn\'t add the getter for property "%s"',
            $method,
            get_class($resource),
            $property
        ));
    }

    protected function parseValue($value, $resource = null)
    {
        if(preg_match('/\$(.+)/', $value, $matches)) {
            $key = $matches[1];
            $request = $this->container->get('request_stack')->getCurrentRequest();
            if($request->attributes->has($key)) {
                return $request->attributes->get($key);
            }
            if($resource !== null) {
                return $this->getProperty($resource, $key);
            }
        }
        return $value;
    }

    protected function renderTemplate($template, $parameters = [])
    {
        return $this->container->get('templating')->render($template, $parameters);
    }

    protected function setOption($key, &$options, $default = null)
    {
        if(!array_key_exists($key, $options)) {
            $options[$key] = $default;
        }
    }

    protected function getOption($key, $options, $default = null)
    {
        if(array_key_exists($key, $options)) {
            return $options[$key];
        }
        return $default;
    }
}