<?php

namespace EWZ\Bundle\RecaptchaBundle\Bridge;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrueValidator;

/**
 * Silex Service Provider
 * Inject recaptcha configuration in pimple
 */
class RecaptchaServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container)
    {
        // parameters
        $container['ewz_recaptcha.public_key']  = null;
        $container['ewz_recaptcha.private_key'] = null;
        $container['ewz_recaptcha.locale_key']  = $container['locale'];
        $container['ewz_recaptcha.enabled']     = true;
        $container['ewz_recaptcha.ajax']        = false;
        $container['ewz_recaptcha.http_proxy']  = array(
            'host' => null,
            'port' => null,
            'auth' => null
        );

        // add loader for EWZ Template
        if (isset($container['twig'])) {
            $path = dirname(__FILE__).'/../Resources/views/Form';
            $container['twig.loader']->addLoader(new \Twig_Loader_Filesystem($path));

            $container['twig.form.templates'] = array_merge(
                $container['twig.form.templates'],
                array('ewz_recaptcha_widget.html.twig')
            );
        }

        // register recaptcha form type
        if (isset($container['form.extensions'])) {
            $container['form.extensions'] = $container->extend('form.extensions',
                function($extensions) use ($container) {
                    $extensions[] = new Form\Extension\RecaptchaExtension($container);

                    return $extensions;
            });
        }

        // register recaptcha validator constraint
        if (isset($container['validator.validator_factory'])) {
            $container['ewz_recaptcha.true'] = function ($container) {
                $validator = new IsTrueValidator(
                    $container['ewz_recaptcha.enabled'],
                    $container['ewz_recaptcha.private_key'],
                    $container['request_stack'],
                    $container['ewz_recaptcha.http_proxy']
                );

                return $validator;
            };

            $container['validator.validator_service_ids'] =
                    isset($container['validator.validator_service_ids']) ? $container['validator.validator_service_ids'] : array();
            $container['validator.validator_service_ids'] = array_merge(
                $container['validator.validator_service_ids'],
                array('ewz_recaptcha.true' => 'ewz_recaptcha.true')
            );
        }

        // register translation files
        if (isset($container['translator'])) {
            $container['translator'] = $container->extend('translator', function($translator, $container) {
                $translator->addResource(
                    'xliff',
                    dirname(__FILE__).'/../Resources/translations/validators.'.$container['ewz_recaptcha.locale_key'].'.xlf',
                    $container['ewz_recaptcha.locale_key'],
                    'validators'
                );

                return $translator;
            });
        }
    }
}
