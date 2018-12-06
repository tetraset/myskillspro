<?php

define('WEB_DIR', __DIR__);
// default static server (firstvds.ru)
define('STATIC_SERVER', 'https://static.myskills.pro');
// static server hosting.jino.ru
define('STATIC_SERVER2', 'http://j566426.myjino.ru');
define('STATIC_SERVER3', 'http://snowtankist.myjino.ru');
define('STATIC_SERVER4', 'http://tetraset.myjino.ru');
define('STATIC_SERVER5', 'http://j615247.myjino.ru');
define('UPLOAD_DIR', __DIR__ . '/../web/uploads');
define('UPLOAD_DIR_WEB', STATIC_SERVER . '/uploads');
define('UPLOAD_DIR_WEB_RELATIVE', '/uploads');
define('SALT', 's890s088s');

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Sonata\MediaBundle\SonataMediaBundle(),
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            new Application\Sonata\UserBundle\ApplicationSonataUserBundle(),
            new IAkumaI\SphinxsearchBundle\SphinxsearchBundle(),
            new Application\Sonata\MediaBundle\ApplicationSonataMediaBundle(),
            new Sonata\IntlBundle\SonataIntlBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new Sonata\FormatterBundle\SonataFormatterBundle(),
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            new Liip\ImagineBundle\LiipImagineBundle(),
            new Sonata\CacheBundle\SonataCacheBundle(),
            new Xmon\SonataMediaProviderVideoBundle\XmonSonataMediaProviderVideoBundle(),
            new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Ijanki\Bundle\FtpBundle\IjankiFtpBundle(),

            new MyskillsBundle\MyskillsBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
