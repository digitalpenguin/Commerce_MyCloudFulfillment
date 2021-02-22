<?php
namespace DigitalPenguin\MyCloudFulfillment\Modules;

use modmore\Commerce\Admin\Configuration\About\ComposerPackages;
use modmore\Commerce\Admin\Sections\SimpleSection;
use modmore\Commerce\Admin\Widgets\Form\CheckboxField;
use modmore\Commerce\Admin\Widgets\Form\PasswordField;
use modmore\Commerce\Admin\Widgets\Form\SectionField;
use modmore\Commerce\Admin\Widgets\Form\TextField;
use modmore\Commerce\Events\Admin\PageEvent;
use modmore\Commerce\Modules\BaseModule;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class MyCloudFulfillment extends BaseModule {

    public function getName()
    {
        $this->adapter->loadLexicon('commerce_mycloudfulfillment:default');
        return $this->adapter->lexicon('commerce_mycloudfulfillment');
    }

    public function getAuthor()
    {
        return 'Murray Wood';
    }

    public function getDescription()
    {
        return $this->adapter->lexicon('commerce_mycloudfulfillment.description');
    }

    public function initialize(EventDispatcher $dispatcher)
    {
        // Load our lexicon
        $this->adapter->loadLexicon('commerce_mycloudfulfillment:default');

        // Add the xPDO package, so Commerce can detect the derivative classes
        $root = dirname(__DIR__, 2);
        $path = $root . '/model/';
        $this->adapter->loadPackage('commerce_mycloudfulfillment', $path);

        // Add template path to twig
//        $root = dirname(__DIR__, 2);
//        $this->commerce->view()->addTemplatesPath($root . '/templates/');

        // Add composer libraries to the about section (v0.12+)
        $dispatcher->addListener(\Commerce::EVENT_DASHBOARD_LOAD_ABOUT, [$this, 'addLibrariesToAbout']);
    }

    public function getModuleConfiguration(\comModule $module)
    {
        $fields = [];

        $fields[] = new SectionField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account.description'),
        ]);
        // Checkbox to enable test account keys and endpoints
        $fields[] = new CheckboxField($this->commerce, [
            'name' => 'properties[usetestaccount]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account'),
            'value' => $module->getProperty('usetestaccount', '')
        ]);

        // Test account fields will only appear if checkbox above is selected and saved.
        if(in_array($module->getProperty('usetestaccount'), [1, true, 'on'])) {
            $fields[] = new SectionField($this->commerce, [
                'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.test_mode'),
                'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.test_mode_desc'),
            ]);
            $fields[] = new TextField($this->commerce, [
                'name' => 'properties[testapikey]',
                'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key'),
                'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key_desc'),
                'value' => $module->getProperty('testapikey', '')
            ]);
            $fields[] = new PasswordField($this->commerce, [
                'name' => 'properties[testsecretkey]',
                'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key'),
                'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key_desc'),
                'value' => $module->getProperty('testsecretkey', '')
            ]);
        }

        // Live account fields are always visible
        $fields[] = new SectionField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.live_mode'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.live_mode_desc'),
        ]);
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[liveapikey]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key_desc'),
            'value' => $module->getProperty('liveapikey', '')
        ]);
        $fields[] = new PasswordField($this->commerce, [
            'name' => 'properties[livesecretkey]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key_desc'),
            'value' => $module->getProperty('livesecretkey', '')
        ]);

        return $fields;
    }

    public function addLibrariesToAbout(PageEvent $event)
    {
        $lockFile = dirname(__DIR__, 2) . '/composer.lock';
        if (file_exists($lockFile)) {
            $section = new SimpleSection($this->commerce);
            $section->addWidget(new ComposerPackages($this->commerce, [
                'lockFile' => $lockFile,
                'heading' => $this->adapter->lexicon('commerce.about.open_source_libraries') . ' - ' . $this->adapter->lexicon('commerce_mycloudfulfillment'),
                'introduction' => '', // Could add information about how libraries are used, if you'd like
            ]));

            $about = $event->getPage();
            $about->addSection($section);
        }
    }
}
