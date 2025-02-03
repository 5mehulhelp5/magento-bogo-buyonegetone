<?php
namespace Bogo\BuyOneGetOne\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Framework\Message\ManagerInterface;
use Bogo\BuyOneGetOne\Helper\Data as BogoHelper;

class AddFreeProduct implements ObserverInterface
{
    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var BogoHelper
     */
    protected $bogoHelper;

    /**
     * @param ItemFactory $itemFactory
     * @param ManagerInterface $messageManager
     * @param BogoHelper $bogoHelper
     */
    public function __construct(
        ItemFactory $itemFactory,
        ManagerInterface $messageManager,
        BogoHelper $bogoHelper
    ) {
        $this->itemFactory = $itemFactory;
        $this->messageManager = $messageManager;
        $this->bogoHelper = $bogoHelper;
    }

    /**
     * Add free product when a BOGO-enabled product is added to cart
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            if (!$this->bogoHelper->isEnabled()) {
                return;
            }

            $item = $observer->getEvent()->getData('quote_item');
            if (!$item) {
                return;
            }

            // 如果是免费商品，直接返回
            if ($item->getData('is_bogo_free')) {
                return;
            }

            $product = $item->getProduct();
            if (!$product || !$product->getData('buy_one_get_one')) {
                return;
            }

            $quote = $item->getQuote();
            if (!$quote) {
                return;
            }

            // 创建免费商品
            $freeItem = $this->itemFactory->create();
            $freeItem->setProduct($product)
                ->setQuote($quote)
                ->setQty($item->getQty())
                ->setCustomPrice(0)
                ->setOriginalCustomPrice(0)
                ->setData('is_bogo_free', 1);

            $quote->addItem($freeItem);
            $quote->collectTotals();

            $this->messageManager->addSuccessMessage(
                __('BOGO offer applied: Free %1 has been added!', $product->getName())
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to add free BOGO product: ') . $e->getMessage());
        }
    }
} 