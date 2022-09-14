<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Additionnalorderfields extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'additionnalorderfields';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Valentin Duplan';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('add additionnal order list fields');
        $this->description = $this->l('add additionnal order list fields');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        return parent::install() &&
            $this->registerHook('actionOrderGridDefinitionModifier') &&
            $this->registerHook('actionOrderGridQueryBuilderModifier') &&
            $this->registerHook('actionAdminControllerSetMedia');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitAdditionnalorderfieldsModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAdditionnalorderfieldsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'ADDITIONNALORDERFIELDS_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'ADDITIONNALORDERFIELDS_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'ADDITIONNALORDERFIELDS_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ADDITIONNALORDERFIELDS_LIVE_MODE' => Configuration::get('ADDITIONNALORDERFIELDS_LIVE_MODE', true),
            'ADDITIONNALORDERFIELDS_ACCOUNT_EMAIL' => Configuration::get('ADDITIONNALORDERFIELDS_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'ADDITIONNALORDERFIELDS_ACCOUNT_PASSWORD' => Configuration::get('ADDITIONNALORDERFIELDS_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    
    public function hookActionAdminControllerSetMedia()
    {
        /* Place your code here. */
    }
    public function hookActionOrderGridDefinitionModifier(array $params){
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        /** @var FilterCollection $filters */
        $filters = $definition->getFilters();

        /** @var ColumnCollection */
        $columns = $definition->getColumns();

        $columns
            ->addAfter('id_order',
                (new DataColumn('carrier'))
                    ->setName($this->l('carrier'))
                    ->setOptions([
                        'field' => 'carrier_name',
                    ])
            );
        $filters
            ->add((new Filter('carrier', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Search carrier', [], 'Admin.Actions'),
                    ],
                ])
                ->setAssociatedColumn('carrier'));
        $columns
            ->addAfter('carrier',
                (new DataColumn('reduction'))
                    ->setName($this->l('Reduction'))
                    ->setOptions([
                        'field' => 'reduction_name',
                    ])
            );
        $filters
            ->add((new Filter('reduction', TextType::class))
                ->setTypeOptions([
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->trans('Search reduction name', [], 'Admin.Actions'),
                    ],
                ])
                ->setAssociatedColumn('reduction'));
    }
    public function hookActionOrderGridQueryBuilderModifier(array $params){
   
        $searchQueryBuilder = $params['search_query_builder'];
        /** @var CustomerFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];
        $orderBy = $searchCriteria->getOrderBy();
        if($searchCriteria->getOrderBy() == 'carrier'){
            $orderBy = 'ca.name';

        } elseif($searchCriteria->getOrderBy() == 'reduction'){
            $orderby = 'cr.description';
        } 
        $searchQueryBuilder->orderBy($orderBy, $searchCriteria->getOrderWay());
        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('carrier' === $filterName && $filterValue) {
                $searchQueryBuilder
                    ->where('ca.`name` = \'' . $filterValue . '\'')
                    ->orWhere('ca.`name` LIKE "%'.$filterValue.'%"');
                $searchQueryBuilder->setParameter(':s', $filterValue);
            } elseif('reduction' === $filterName && $filterValue) {
                $searchQueryBuilder
                    ->where('cr.`name` = \'' . $filterValue . '\'')
                    ->orWhere('cr.`name` LIKE "%'.$filterValue.'%"');
                $searchQueryBuilder->setParameter(':s', $filterValue);
            }
        }


        $searchQueryBuilder->addSelect('ca.name as carrier_name');
        $searchQueryBuilder->addSelect('cr.name as reduction_name');
        $searchQueryBuilder->leftJoin('o', _DB_PREFIX_. 'carrier', 'ca', 'o.id_carrier = ca.id_carrier');
        $searchQueryBuilder->leftJoin('o', _DB_PREFIX_. 'order_cart_rule', 'cr', 'o.id_order = cr.id_order');
        //$searchQueryBuilder->leftJoin( _DB_PREFIX_ . 'carrier c on c.id_carrier = o.id_carrier');
          /* dump($params);
        die;*/
    }


}
