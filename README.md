# importcategory
Import Category in Magento 2
<?php
namespace Onealfa\Categoryimport\Controller\Index;
class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    public $_objectManager;
    public $helper;
    public $_success;
    public $_failure;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Estdevs\Employees\Helper\Data $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
       $this->helper = $helper;
        return parent::__construct($context);
    }

    public function execute()
    {

      // authenticate the user
      // read the xml category
      $xmlfilepath = '';
      if(isset($_POST['xmlpath'])) {
         $xmlfilepath = $_POST['xmlpath'];
         // parse xml
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
         $category = $objectManager->create('\Magento\Framework\Xml\Parser');
         $data = $category->load($xmlfilepath)->xmlToArray();

         // echo count($data['Categories']['Category']);
         // print_r($data);
         if(count($data['Categories']['Category']) === '1'){
          $this->importCat(array($data['Categories']['Category']));

         } else {
            $this->importCat($data['Categories']);
         }

      }
    }

    public function importCat($data, $_id = null)
    {

      foreach ($data as $key => $_category) {
            if($key === 'Category') {

              if(array_key_exists('Category', $_category)){
                   $_parentid = $this->prepareData($_category);
                   $this->importCat(array($_category['Category']), $_parentid);
              }
              else{
                $this->importCat($_category);
              }

            }
            else {
                if(array_key_exists('Category', $_category)){
                   $_parentid = $this->prepareData($_category);
                   $this->importCat(array($_category['Category']), $_parentid);
                } else {
                   $this->prepareData($_category, $_id);
                }
            }
      }
    }

    /**
     * @param string $row
     * @param int $id
     * @return array mixed
     */
    protected function prepareData($row, $id = null)
    {
          // in case you want to user the root category id
//        $rootCat = $this->_objectManager->get('Magento\Catalog\Model\Category');
//        $cat_info = $rootCat->load($rootrowId);
//        $categoryTmp->setPath($rootCat->getPath());

      // $catname = isset($row['Description']) ? $row['Description'] : null;
      // if($catname === null) {
      //     $this->_failure++;
      //     return;
      // }

        if($id === null) {
          $id = 2;
        }

        $data = [
            'data' => [
                "parent_id" => $id,
                'name' => @$row['Name'],
                'description' => @$row['Description'],
                "is_active" => true,
                "position" => 10,
                "include_in_menu" => false,
            ],
            'custom_attributes' => [
                "display_mode"=> "PRODUCTS",
                "is_anchor"=> "1",
//                "custom_use_parent_settings"=> "0",
//                "custom_apply_to_products"=> "0",
//                "url_key"=> "", // if not set magento uses the name to generate it
//                "url_path"=> "cat2",
//                "automatic_sorting"=> "0",
//                'my_own_attribute' => 'value' // <-- your attribute
            ]
        ];

        print_r($data);die();

        // if($id) {
        //     $data['data']['id'] = $id;
        // }

        return $this->createCategory($data);
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function createCategory(array $data)
    {
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $category =  $objectManager
        ->create('Magento\Catalog\Model\Category', $data)
        ->setCustomAttributes($data['custom_attributes']);

      $repository =  $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);

      $id = 2;

      try {
        $result = $repository->save($category);

        $id = $result->getId();

        // die('wait');
        $this->_success++;
        echo "Created Category " . $data['data']['name'] . "\n";
      } catch (Exception $e) {
        $this->_failure++;
      }

      return $id;
    }

}
