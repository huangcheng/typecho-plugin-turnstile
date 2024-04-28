<?php

namespace TypechoPlugin\Turnstile;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Password;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Add Cloudflare Turnstile to Your Typecho Login Form.
 *
 * @package Turnstile
 * @author HUANG Cheng
 * @version 1.0.0
 * @link https://cheng.sh
 */
class Plugin implements PluginInterface
{
    public static string $Name = 'Turnstile';
    /**
     * Activate plugin method, if activated failed, throw exception will disable this plugin.
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/footer.php')->begin =  __CLASS__ . '::render';
    }

    /**
     * Deactivate plugin method, if deactivated failed, throw exception will enable this plugin.
     */
    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    /**
     * Plugin config panel render method.
     *
     * @param Form $form
     */
    public static function config(Form $form)
    {
        $siteKey = new Text('siteKey', null, null, _t('Site Key'));
        $secretKey = new Password('secretKey', null, null, _t('Secret Key'));

        $form->addInput($siteKey);
        $form->addInput($secretKey);
    }

    /**
     * Plugin personal config panel render method.
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function render(): void
    {
        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>';
        echo '
            <script>
               $(function () {               
                    const isLoginPage = $("form[name=\"login\"]").length > 0;
                    const sitekey = "' . Options::alloc()->plugin(Plugin::$Name)->siteKey . '";
                    
                    if (!isLoginPage || !sitekey) {
                        return;
                    }
                    
                    $("<div id=\"turnstile-container\"></div>").insertBefore("p.submit");
                    
                    turnstile.ready(function() {
                          turnstile.render("#turnstile-container", {
                            sitekey,
                          });
                    });
              });
            </script>
        ';
    }
}

