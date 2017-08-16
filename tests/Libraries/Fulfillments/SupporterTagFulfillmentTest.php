<?php

/**
 *    Copyright 2015-2017 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests;

use App\Libraries\Fulfillments\SupporterTagFulfillment;
use App\Models\User;
use App\Models\Store\Order;
use App\Models\Store\OrderItem;
use App\Models\Store\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;
use TestCase;

class SupporterTagFulfillmentTest extends TestCase
{
    use DatabaseTransactions;

    public function setup()
    {
        parent::setup();

        $this->product = $this->product();
        $this->user = factory(User::class)->create([
            'osu_featurevotes' => 0,
            'osu_subscriptionexpiry' => Carbon::now(),
        ]);
        $this->order = factory(Order::class)->create([
            'user_id' => $this->user->user_id,
            'transaction_id' => 'test-'.time(),
        ]);
    }

    public function testDonateSupporterTagToSelf()
    {
        $donor = $this->user;
        $expectedExpiry = $donor->osu_subscriptionexpiry->addMonths(1);
        $this->createOrderItem($this->user, 1, 4);

        $fulfiller = new SupporterTagFulfillment($this->order);
        $fulfiller->run();

        $donor->refresh();
        $this->assertTrue($donor->osu_subscriber);
        $this->assertEquals(
            $expectedExpiry->format('Y-m-d'),
            $donor->osu_subscriptionexpiry
        );
        $this->assertEquals(
            2,
            $donor->osu_featurevotes
        );
    }

    public function testDonateSupporterTagToOthers()
    {
        $now = Carbon::now();

        $donor = $this->user;
        $giftee = factory(\App\Models\User::class)->create([
            'osu_featurevotes' => 0,
            'osu_subscriptionexpiry' => $now->copy(),
        ]);
        $expectedExpiry = $giftee->osu_subscriptionexpiry->addMonths(1);

        $this->createOrderItem($giftee, 1, 4);

        $fulfiller = new SupporterTagFulfillment($this->order);
        $fulfiller->run();

        $donor->refresh();
        $giftee->refresh();

        // giftee gets subscription, not donor.
        $this->assertFalse($donor->osu_subscriber);
        $this->assertTrue($giftee->osu_subscriber);

        // donor's expiry should not change.
        $this->assertEquals($expectedExpiry->format('Y-m-d'), $giftee->osu_subscriptionexpiry);
        $this->assertEquals($now->format('Y-m-d'), $donor->osu_subscriptionexpiry);

        // votes go to donor.
        $this->assertEquals(2, $donor->osu_featurevotes);
        $this->assertEquals(0, $giftee->osu_featurevotes);
    }

    public function testPartiallyFulfilledOrder()
    {
        $this->markTestIncomplete();
    }

    public function testAlreadyFulfilledOrder()
    {
        $this->markTestIncomplete();
    }

    public function testRevoke()
    {
        $this->markTestIncomplete();
    }

    public function testPartiallyRevokedOrder()
    {
        $this->markTestIncomplete();
    }

    public function testAlreadyRevokedOrder()
    {
        $this->markTestIncomplete();
    }

    private function product()
    {
        return Product::customClass('supporter-tag')->first(); // should already exist from migrations
    }

    private function createOrderItem($user, $duration, $amount)
    {
        return factory(OrderItem::class)->create([
            'order_id' => $this->order->order_id,
            'product_id' => $this->product->product_id,
            'cost' => $amount,
            'extra_data' => [
                'target_id' => $user->user_id,
                'duration' => $duration,
            ],
        ]);
    }
}
