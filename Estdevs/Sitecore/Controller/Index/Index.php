<?php
namespace Estdevs\Sitecore\Controller\Index;
class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        return parent::__construct($context);
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
         $table = 'authorization_role';
        $connection = $resource->getConnection();

        $select = $connection->select()->from(
            $table,array('role_id','role_name')
        );


        // $binds = ['user_id' => (int)$user->getId(),
        //           'user_type' => UserContextInterface::USER_TYPE_ADMIN
        // ];

        $roles = $connection->fetchall($select);
        echo "<pre>";
        print_r($roles);



            die('what');
        return $this->resultPageFactory->create();
    }
}
