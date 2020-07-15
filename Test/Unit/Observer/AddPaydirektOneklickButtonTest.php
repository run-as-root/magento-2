<?php

/**
 * PAYONE Magento 2 Connector is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PAYONE Magento 2 Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PAYONE Magento 2 Connector. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category  Payone
 * @package   Payone_Magento2_Plugin
 * @author    FATCHIP GmbH <support@fatchip.de>
 * @copyright 2003 - 2019 Payone GmbH
 * @license   <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link      http://www.payone.de
 */

namespace Payone\Core\Test\Unit\Observer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\Paypal\Block\Express\Shortcut;
use Payone\Core\Helper\Payment;
use Payone\Core\Observer\AddPaydirektOneklickButton as ClassToTest;
use Payone\Core\Test\Unit\BaseTestCase;
use Payone\Core\Test\Unit\PayoneObjectManager;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;

class AddPaydirektOneklickButtonTest extends BaseTestCase
{
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var ObjectManager|PayoneObjectManager
     */
    private $objectManager;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentHelper;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->paymentHelper = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();

        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayonePaydirektRegistered'])
            ->getMock();
        $customer->method('getPayonePaydirektRegistered')->willReturn(true);

        $this->customerSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->customerSession->method('getCustomer')->willReturn($customer);

        $this->classToTest = $this->objectManager->getObject(ClassToTest::class, [
            'paymentHelper' => $this->paymentHelper,
            'customerSession' => $this->customerSession,
        ]);
    }

    public function testExecutePaydirektInactive()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(false);
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteWrongContainer()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);

        $shortcutButtons = $this->getMockBuilder(ShortcutButtons::class)->disableOriginalConstructor()->getMock();
        $shortcutButtons->method('getNameInLayout')->willReturn('addtocart.shortcut.buttons');

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();
        $event->method('getContainer')->willReturn($shortcutButtons);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecuteNotLoggedInContainer()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);

        $shortcutButtons = $this->getMockBuilder(ShortcutButtons::class)->disableOriginalConstructor()->getMock();
        $shortcutButtons->method('getNameInLayout')->willReturn('test');

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();
        $event->method('getContainer')->willReturn($shortcutButtons);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $this->customerSession->method('isLoggedIn')->willReturn(false);

        $result = $this->classToTest->execute($observer);
        $this->assertNull($result);
    }

    public function testExecutePaydirektActive()
    {
        $this->paymentHelper->method('isPaymentMethodActive')->willReturn(true);

        $shortcut = $this->getMockBuilder(Shortcut::class)->disableOriginalConstructor()->getMock();

        $layout = $this->getMockBuilder(LayoutInterface::class)->disableOriginalConstructor()->getMock();
        $layout->method('createBlock')->willReturn($shortcut);

        $shortcutButtons = $this->getMockBuilder(ShortcutButtons::class)->disableOriginalConstructor()->getMock();
        $shortcutButtons->method('getNameInLayout')->willReturn('test');
        $shortcutButtons->method('getLayout')->willReturn($layout);

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContainer'])
            ->getMock();
        $event->method('getContainer')->willReturn($shortcutButtons);

        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observer->method('getEvent')->willReturn($event);

        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $result = $this->classToTest->execute($observer);
        $executed = true;
        $this->assertTrue($executed);
    }
}