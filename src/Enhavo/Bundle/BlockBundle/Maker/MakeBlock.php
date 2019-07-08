<?php

namespace Enhavo\Bundle\BlockBundle\Maker;

use Enhavo\Bundle\AppBundle\Maker\MakerUtil;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Templating\EngineInterface;

class MakeBlock extends AbstractMaker
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var MakerUtil
     */
    private $util;

    /**
     * @var EngineInterface
     */
    private $templateEngine;

    public function __construct(KernelInterface $kernel, MakerUtil $util, EngineInterface $templateEngine)
    {
        $this->kernel = $kernel;
        $this->util = $util;
        $this->templateEngine = $templateEngine;
    }

    public static function getCommandName(): string
    {
        return 'make:enhavo:block';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
        ->setDescription('Creates a new block')
        ->addArgument(
            'bundleName',
            InputArgument::REQUIRED,
            'What is the name of the bundle the new block should be added to?'
        )
        ->addArgument(
            'blockName',
            InputArgument::REQUIRED,
            'What is the name the block should have (Without "Block" postfix)?'
        )
        ->addArgument(
            'blockType',
            InputArgument::REQUIRED,
            'Create block type? [no/yes]'
        );
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {

    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $bundleName = $input->getArgument('bundleName');
        $blockName = $input->getArgument('blockName');
        $blockType = $input->getArgument('blockType');
        $blockType = $blockType === 'yes' ? true : false;

        $bundle = $this->kernel->getBundle($bundleName);

        $blockSubDirectory = '';
        $this->splitBlockNameSubDirectory($blockName, $blockSubDirectory);
        $originName = $blockName;
        $blockName = sprintf('%sBlock', $blockName);

        $this->generateDoctrineOrmFile($generator, $bundle, $blockName, $blockSubDirectory);
        $this->generateEntityFile($generator, $bundle, $blockName, $blockSubDirectory);
        $this->generateFormTypeFile($generator, $bundle, $blockName, $blockSubDirectory);
        $this->generateFactoryFile($generator, $bundle, $blockName, $blockSubDirectory);
        $this->generateTemplateFile($generator, $bundle, $blockName, $originName, $blockSubDirectory);
        if($blockType) {
            $this->generateTypeFile($generator, $bundle, $blockName, $originName, $blockSubDirectory);
        }

        $io->writeln('');
        $io->writeln('<options=bold>Add this to your enhavo.yml config file under enhavo_block -> blocks:</>');
        $io->writeln($this->generateEnhavoConfigCode($bundle, $blockName, $originName, $blockSubDirectory, $blockType));
        $io->writeln('');
        if($blockType) {
            $io->writeln('<options=bold>Add this to your service.yml config</>');
            $io->writeln($this->generateServiceCode($bundle, $blockName, $originName, $blockSubDirectory));
        }
        $io->writeln('');

        $generator->writeChanges();
    }

    private function generateDoctrineOrmFile(Generator $generator, BundleInterface $bundle, $blockName, $blockSubDirectory)
    {
        $blockFileName = $blockName;
        if ($blockSubDirectory) {
            $blockFileName = str_replace('/', '.', $blockSubDirectory) . '.' . $blockName;
        }
        $filePath = $bundle->getPath() . '/Resources/config/doctrine/' . $blockFileName . '.orm.yml';
        $this->createPathToFileIfNotExists($filePath);
        if (file_exists($filePath)) {
            throw new \RuntimeException('Entity "' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . '" already exists in bundle "' . $bundle->getName() . '".');
        }

        $bundleNameSnakeCase = $this->util->camelCaseToSnakeCase($this->util->getBundleNameWithoutPostfix($bundle));
        $blockNameSnakeCase = $this->util->camelCaseToSnakeCase($blockName);
        $blockSubDirectorySnakeCase = str_replace('/', '', $this->util->camelCaseToSnakeCase($blockSubDirectory));

        $generator->generateFile(
            $filePath,
            $this->createTemplatePath('block/doctrine.tpl.php'),
            [
                'bundle_namespace' => $bundle->getNamespace(),
                'block_sub_directory' => str_replace('/', '\\', $blockSubDirectory),
                'block_name' => $blockName,
                'table_name' => $bundleNameSnakeCase . '_' . ($blockSubDirectorySnakeCase ? $blockSubDirectorySnakeCase . '_' : '') . $blockNameSnakeCase
            ]
        );
    }

    private function generateEntityFile(Generator $generator, BundleInterface $bundle, $blockName, $blockSubDirectory)
    {
        $filePath = $bundle->getPath() . '/Entity/' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . '.php';
        $this->createPathToFileIfNotExists($filePath);
        if (file_exists($filePath)) {
            throw new \RuntimeException('Entity "' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . '" already exists in bundle "' . $bundle->getName() . '".');
        }

        $generator->generateFile(
            $filePath,
            $this->createTemplatePath('block/entity.tpl.php'),
            [
                'namespace' => $this->getNameSpace($bundle, '\\Entity', $blockSubDirectory),
                'block_name' => $blockName
            ]
        );
    }

    private function generateFormTypeFile(Generator $generator, BundleInterface $bundle, $blockName, $blockSubDirectory)
    {
        $filePath = $bundle->getPath() . '/Form/Type/' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . 'Type.php';
        $this->createPathToFileIfNotExists($filePath);
        if (file_exists($filePath)) {
            throw new \RuntimeException('FormType "' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . 'Type" already exists in bundle "' . $bundle->getName() . '".');
        }

        $generator->generateFile(
            $filePath,
            $this->createTemplatePath('block/form-type.tpl.php'),
            [
                'namespace' => $this->getNameSpace($bundle, '\\Form\\Type', $blockSubDirectory),
                'block_name' => $blockName,
                'block_namespace' => $this->getNameSpace($bundle, '\\Entity', $blockSubDirectory) . '\\' . $blockName,
                'form_type_name' => $this->getFormTypeName($bundle, $blockName, $blockSubDirectory)
            ]
        );
    }

    private function generateFactoryFile(Generator $generator, BundleInterface $bundle, $blockName, $blockSubDirectory)
    {
        $filePath = $bundle->getPath() . '/Factory/' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . 'Factory.php';
        $this->createPathToFileIfNotExists($filePath);
        if (file_exists($filePath)) {
            throw new \RuntimeException('Factory class "' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . 'Factory" already exists in bundle "' . $bundle->getName() . '".');
        }

        $generator->generateFile(
            $filePath,
            $this->createTemplatePath('block/factory.tpl.php'),
            [
                'namespace' => $this->getNameSpace($bundle, '\\Factory', $blockSubDirectory),
                'block_name' => $blockName
            ]
        );
    }

    private function generateTemplateFile(Generator $generator, BundleInterface $bundle, $blockName, $originName, $blockSubDirectory)
    {
        $filePath = $bundle->getPath() . '/Resources/views/Theme/Block/' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $this->util->camelCaseToSnakeCase($originName, true) . '.html.twig';
        $this->createPathToFileIfNotExists($filePath);
        if (file_exists($filePath)) {
            throw new \RuntimeException('Frontend template file "' . $filePath . '" already exists.');
        }

        $generator->generateFile(
            $filePath,
            $this->createTemplatePath('block/template.tpl.php'),
            [
                'block_name' => $blockName,
            ]
        );
    }

    private function generateTypeFile(Generator $generator, BundleInterface $bundle, $blockName, $originName, $blockSubDirectory)
    {
        $filePath = $bundle->getPath() . '/Block/' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . 'Type.php';
        $this->createPathToFileIfNotExists($filePath);
        if (file_exists($filePath)) {
            throw new \RuntimeException('BlockType "' . ($blockSubDirectory ? $blockSubDirectory . '/' : '') . $blockName . '" already exists in bundle "' . $bundle->getName() . '".');
        }

        $generator->generateFile(
            $filePath,
            $this->createTemplatePath('block/block-type.tpl.php'),
            [
                'namespace' => $this->getNameSpace($bundle, '', $blockSubDirectory),
                'block_name' => $blockName,
                'name_camel' => $originName,
                'name_kebab' => $this->util->camelCaseToSnakeCase($originName, true),
                'name_snake' => $this->util->camelCaseToSnakeCase($originName),
                'name_underscore' => str_replace('-', '_', $this->util->camelCaseToSnakeCase($originName)),
                'bundle_name' => $bundle->getName(),
            ]
        );
    }

    private function generateEnhavoConfigCode(BundleInterface $bundle, $blockName, $originName, $blockSubDirectory, $blockType)
    {
        $blockNameSpace = $this->getNameSpace($bundle, '\\Entity', $blockSubDirectory) . '\\' . $blockName;
        $formTypeNamespace = $this->getNameSpace($bundle, '\\Form\\Type', $blockSubDirectory) . '\\' .$blockName . 'Type';
        $template = $bundle->getName() . ':Theme/Block' . ($blockSubDirectory ? '/' . $blockSubDirectory : '') . ':' . $this->util->camelCaseToSnakeCase($originName, true) . '.html.twig';
        $factoryNamespace = $this->getNameSpace($bundle, '\\Factory', $blockSubDirectory) . '\\' . $blockName . 'Factory';

        return $this->templateEngine->render('@EnhavoBlock/Maker/Block/enhavo_config_entry.yml.twig', array(
            'block_name' => $blockName,
            'origin_name' => $this->util->camelCaseToSnakeCase($originName),
            'bundle_name' => $bundle->getName(),
            'block_name_snake_case' => $this->util->camelCaseToSnakeCase($blockName),
            'block_namespace' => $blockNameSpace,
            'form_type_namespace' => $formTypeNamespace,
            'template' => $template,
            'factory_namespace' => $factoryNamespace,
            'block_type' => $blockType,
        ));
    }

    private function generateServiceCode(BundleInterface $bundle, $blockName, $originName, $blockSubDirectory)
    {
        $class = $this->getNameSpace($bundle, '\\Block', $blockSubDirectory) . '\\' . $blockName . 'Type';
        return $this->templateEngine->render('@EnhavoBlock/Maker/Block/block_service.yml.twig', array(
            'class' => $class,
            'type' => $this->util->camelCaseToSnakeCase($originName),
        ));
    }

    private function getFormTypeName(BundleInterface $bundle, $blockName, $blockSubDirectory)
    {
        $blockSubDirectorySnakeCase = str_replace('/', '', $this->util->camelCaseToSnakeCase($blockSubDirectory));

        return
            $this->util->camelCaseToSnakeCase($this->util->getBundleNameWithoutPostfix($bundle))
            . '_' . ($blockSubDirectorySnakeCase ? $blockSubDirectorySnakeCase . '_' : '') . $this->util->camelCaseToSnakeCase($blockName);
    }

    private function splitBlockNameSubDirectory(&$blockName, &$subDirectory)
    {
        $subDirectory = null;
        $matches = array();
        if (preg_match('/^(.*)\/([^\/]*)$/', $blockName, $matches)) {
            $subDirectory = $matches[1];
            $blockName = $matches[2];
        }
    }

    private function getNameSpace(BundleInterface $bundle, $staticPath, $blockSubDirectory)
    {
        return $bundle->getNamespace() . $staticPath . ($blockSubDirectory ? '\\' . str_replace('/', '\\', $blockSubDirectory) : '');
    }

    private function createPathToFileIfNotExists($fullFileName)
    {
        $info = pathinfo($fullFileName);
        if (!file_exists($info['dirname'])) {
            mkdir($info['dirname'], 0777, true);
        }
    }

    private function createTemplatePath($name)
    {
        return __DIR__.'/../Resources/skeleton/'.$name;
    }
}