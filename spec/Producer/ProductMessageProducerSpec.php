<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Sulu\SyliusProducerPlugin\Producer;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sulu\Bundle\SyliusConsumerBundle\Model\Product\Message\RemoveProductMessage;
use Sulu\Bundle\SyliusConsumerBundle\Model\Product\Message\SynchronizeProductMessage;
use Sulu\SyliusProducerPlugin\Producer\ProductMessageProducer;
use Sulu\SyliusProducerPlugin\Producer\ProductVariantMessageProducerInterface;
use Sulu\SyliusProducerPlugin\Producer\Serializer\ProductSerializerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductMessageProducerSpec extends ObjectBehavior
{
    public function let(
        ProductSerializerInterface $productSerializer,
        MessageBusInterface $messageBus,
        ProductVariantMessageProducerInterface $productVariantMessageProducer
    ): void {
        $this->beConstructedWith($productSerializer, $messageBus, $productVariantMessageProducer);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ProductMessageProducer::class);
    }

    public function it_should_dispatch_synchronize_message(
        ProductSerializerInterface $productSerializer,
        MessageBusInterface $messageBus,
        ProductInterface $product
    ): void {
        $product->getCode()->willReturn('product-1');
        $product->isSimple()->willReturn(false);
        $productSerializer->serialize($product)->shouldBeCalled()->willReturn(['code' => 'product-1']);

        $this->synchronize($product);

        $messageBus->dispatch(
            Argument::that(
                function (SynchronizeProductMessage $message) {
                    return 'product-1' === $message->getCode()
                        && ['code' => 'product-1'] === $message->getPayload();
                }
            )
        )->shouldBeCalled();
    }

    public function it_should_dispatch_synchronize_message_with_simple_product(
        ProductSerializerInterface $productSerializer,
        ProductVariantMessageProducerInterface $productVariantMessageProducer,
        MessageBusInterface $messageBus,
        ProductInterface $product,
        ProductVariantInterface $productVariant1,
        ProductVariantInterface $productVariant2
    ): void {
        $product->getCode()->willReturn('product-1');
        $product->isSimple()->willReturn(true);
        $product->getVariants()->willReturn(new ArrayCollection(
            [$productVariant1->getWrappedObject(), $productVariant2->getWrappedObject()])
        );
        $productSerializer->serialize($product)
            ->shouldBeCalled()->willReturn(['code' => 'product-1']);

        $productVariantMessageProducer->synchronize($productVariant1)->shouldBeCalled();
        $productVariantMessageProducer->synchronize($productVariant2)->shouldBeCalled();

        $this->synchronize($product);

        $messageBus->dispatch(
            Argument::that(
                function (SynchronizeProductMessage $message) {
                    return 'product-1' === $message->getCode()
                        && ['code' => 'product-1'] === $message->getPayload();
                }
            )
        )->shouldBeCalled();
    }

    public function it_should_dispatch_remove_message(
        MessageBusInterface $messageBus,
        ProductInterface $product
    ): void {
        $product->getCode()->willReturn('product-1');

        $this->remove($product);

        $messageBus->dispatch(
            Argument::that(
                function (RemoveProductMessage $message) {
                    return 'product-1' === $message->getCode();
                }
            )
        )->shouldBeCalled();
    }
}
