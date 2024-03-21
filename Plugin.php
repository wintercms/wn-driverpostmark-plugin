<?php namespace Winter\DriverPostmark;

use App;
use Event;
use System\Classes\PluginBase;
use System\Models\MailSetting;

use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;

/**
 * DriverPostmark Plugin Information File
 */
class Plugin extends PluginBase
{
    public $elevated = true;
    
    const MODE_POSTMARK = 'postmark';

    public function pluginDetails()
    {
        return [
            'name'        => 'winter.driverpostmark::lang.plugin.name',
            'description' => 'winter.driverpostmark::lang.plugin.description',
            'homepage'    => 'https://github.com/wintercms/wn-driverpostmark-plugin',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-leaf',
        ];
    }

    public function register()
    {
        Event::listen('mailer.beforeRegister', function ($mailManager) {
            $mailManager->extend(self::MODE_POSTMARK, function ($config) {
                $factory = new PostmarkTransportFactory();

                if (!isset($config['secret'])) {
                    $config = $this->app['config']->get('services.postmark', []);
                }

                return $factory->create(new Dsn(
                    'postmark+'.($config['scheme'] ?? 'api'),
                    $config['endpoint'] ?? 'default',
                    $config['secret']
                ));
            });

            $settings = MailSetting::instance();
            if ($settings->send_mode === self::MODE_POSTMARK) {
                $config = App::make('config');
                $config->set('mail.mailers.postmark.transport', self::MODE_POSTMARK);
                $config->set('services.postmark.secret', $settings->postmark_secret);
            }
        });
    }

    public function boot()
    {
        MailSetting::extend(function ($model) {
            $model->bindEvent('model.beforeValidate', function () use ($model) {
                $model->rules['postmark_secret'] = 'required_if:send_mode,' . self::MODE_POSTMARK;
            });
            $model->postmark_secret = config('services.postmark.secret');
        });

        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->getController() instanceof \System\Controllers\Settings) {
                return;
            }
            if (!$widget->model instanceof MailSetting) {
                return;
            }

            $field = $widget->getField('send_mode');
            $field->options(array_merge($field->options(), [self::MODE_POSTMARK => 'Postmark']));

            $widget->addTabFields([
                'postmark_secret' => [
                    'tab'     => 'system::lang.mail.general',
                    'label'   => 'winter.driverpostmark::lang.postmark_secret',
                    'type'    => 'sensitive',
                    'commentAbove' => 'winter.driverpostmark::lang.postmark_secret_comment',
                    'trigger' => [
                        'action'    => 'show',
                        'field'     => 'send_mode',
                        'condition' => 'value[postmark]'
                    ]
                ],
            ]);
        });
    }
}
