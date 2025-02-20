<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

namespace AppBundle\Extract\Dumper;

use PrestaShop\TranslationToolsBundle\Configuration;
use PrestaShop\TranslationToolsBundle\Translation\Dumper\XliffFileDumper as BaseXliffFileDumper;
use PrestaShop\TranslationToolsBundle\Translation\Builder\XliffBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;

class XliffFileDumper extends BaseXliffFileDumper
{
    /**
     * {@inheritdoc}
     */
    public function dump(MessageCatalogue $messages, $options = [])
    {
        if (!array_key_exists('path', $options)) {
            throw new \InvalidArgumentException('The file dumper needs a path option.');
        }

        $fs = new Filesystem();
        // save a file for each domain
        foreach ($messages->getDomains() as $domain) {
            $domainPath = str_replace('.', '', $domain);
            $fullpath = sprintf('%s/%s/%s.%s.%s', $options['path'], $messages->getLocale(), $domainPath, $options['default_locale'], $this->getExtension());
            $directory = dirname($fullpath);
            if (!file_exists($directory) && !@mkdir($directory, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create directory "%s".', $directory));
            }

            $fs->dumpFile($fullpath, $this->formatCatalogue($messages, $domain, $options));
        }
    }

    public function formatCatalogue(MessageCatalogue $messages, $domain, array $options = []): string
    {
        if (array_key_exists('default_locale', $options)) {
            $defaultLocale = $options['default_locale'];
        } else {
            $defaultLocale = \Locale::getDefault();
        }

        $xliffBuilder = new XliffBuilder();
        $xliffBuilder->setVersion('1.2');

        $fileName = $domain;

        foreach ($messages->all($domain) as $source => $target) {
            if (!empty($source)) {
                $metadata = $messages->getMetadata($source, $domain);

                if (!empty($metadata['file'])) {
                    $metadata['file'] = Configuration::getRelativePath(
                        $metadata['file'],
                        !empty($options['root_dir']) ? realpath($options['root_dir']) : false
                    );
                }


                $xliffBuilder->addFile($fileName, $defaultLocale, $messages->getLocale());
                $xliffBuilder->addTransUnit($fileName, $source, $target, $this->getNote($metadata));
            }
        }

        return html_entity_decode($xliffBuilder->build()->saveXML());
    }

    private function getNote($transMetadata): string
    {
        $notes = [];

        if (!empty($transMetadata['file'])) {
            if (isset($transMetadata['line'])) {
                $notes[] = str_replace(DIRECTORY_SEPARATOR, '/', $transMetadata['file']) . ':' . $transMetadata['line'];
            } else {
                $notes[] = str_replace(DIRECTORY_SEPARATOR, '/', $transMetadata['file']);
            }
        }

        return implode(PHP_EOL, $notes);
    }
}
