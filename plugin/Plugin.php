<?php

namespace TypechoPlugin\Turnstile;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Password;
use Widget\Options;
use Widget\User;

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
    public static string $TurnstileUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    /**
     * Activate plugin method, if activated failed, throw exception will disable this plugin.
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/footer.php')->begin =  __CLASS__ . '::render';

        \Typecho\Plugin::factory('Widget_User')->loginSucceed =  __CLASS__ . '::loginSucceed';
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
        
        const $submit = $("button[type=\"submit\"]");
        
        $submit.prop("disabled", true);
        
        $("<div id=\"turnstile-container\"></div>").insertBefore("p.submit");
        
        turnstile.ready(function() {
              turnstile.render("#turnstile-container", {
                sitekey,
                language: "zh_CN",
                callback: (token) => {
                    $submit.prop("disabled", false);
                }
              });
        });
  });
</script>
        ';
    }

    public static function loginSucceed(User $user, string $username, string $password, bool $remember): void {
        @session_start();

        $cf_turnstile_response = $_REQUEST['cf-turnstile-response'];

        if (!is_string($cf_turnstile_response) || strlen($cf_turnstile_response) <= 0) {
            Widget::widget('Widget_Notice')->set('请完成人机验证', 'error');
            $user->logout();

            return;
        }

        $secret = Options::alloc()->plugin(Plugin::$Name)->secretKey;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, Plugin::$TurnstileUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $secret,
            'response' => $cf_turnstile_response
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

        if ($response['success'] === true) {
            return ;
        }

        Widget::widget('Widget_Notice')->set('人机验证失败', 'error');
        $user->logout();
    }
}

