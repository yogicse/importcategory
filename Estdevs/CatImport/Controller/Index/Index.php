<?php
namespace Estdevs\CatImport\Controller\Index;
class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $request;
    public $xmlPath = null;
    public $_success = 0;
    public $_failure =0;
    public $_skip = 0;
    public $storeRootCategory = null;
    protected $objectmanager;
    protected $result = array();

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->objectmanager = \Magento\Framework\App\ObjectManager::getInstance();
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->xmlPath = $this->request->getParam('xmlpath');
        if($this->xmlPath === null || empty($this->xmlPath)) {
            $this->result['error'] = 'Xml path is not valid.';
        }

        if (@fopen($this->xmlPath,"r")==true) {
            $data = $this->isValidXml();
            $this->_getRootCategory();

            if(count($data['Categories']['Category']) > 0) {
                $this->ImportCategory($data['Categories']['Category']);
            }
        } else {
             $this->result['error'] = 'Xml path does not exist.';
        }

        $this->result['success'] = $this->_success."  Category import successfully.";
        $this->result['skip'] = $this->_skip."  Category skipped as already exists.";
        $this->result['failed'] = $this->_failure." failed to import.";

        echo json_encode($this->result);
    }

    /**
     * isValidXml - check the xml and return if have valid xml file
     * @return boolean
     */
    public function isValidXml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $category = $objectManager->get('\Magento\Framework\Xml\Parser');
        try {
           return $data = $category->load($this->xmlPath)->xmlToArray();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function _getRootCategory()
    {
        $storeManager = $this->objectmanager->get('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        $this->storeRootCategory = $store->getRootCategoryId();
    }

    private function _isCategoryExists($title = null)
    {
        if($title === null) {
            return false;
        }

        $objectManager =   \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->getCollection()->addFieldToFilter('name',$title);

        return $collection->getFirstItem()->getId();
    }


    public function ImportCategory($data) {
        foreach ($data as $key => $_cat) {
            $parentId = $this->_isCategoryExists($_cat['Parent']);
            $parentId = empty($parentId) ? $this->storeRootCategory : $parentId;

            if($this->_isCategoryExists($_cat['Name'])){
                $this->_skip++;
                continue;
            }

            $catData = [
                'data' => [
                    "parent_id" => $parentId,
                    'name' => @$_cat['Name'],
                    'description' => @$_cat['Description'],
                    "is_active" => true,
                    "position" => 10,
                    "include_in_menu" => false,
                ],
                'custom_attributes' => [
                    "display_mode"=> "PRODUCTS",
                    "is_anchor"=> "1",
                ]
            ];
            $this->createCategory($catData);
        }
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
        $mediaAttribute = array ('image', 'small_image', 'thumbnail');
        $categoryTmp->setImage('/m2.png', $mediaAttribute, true, false);// Path pub/meida/catalog/category/m2.png
        $repository =  $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
        try {
            $result = $repository->save($category);
            $id = $result->getId();
            $this->_success++;
          } catch (Exception $e) {
            $this->_failure++;
          }

        return true;
    }


}
