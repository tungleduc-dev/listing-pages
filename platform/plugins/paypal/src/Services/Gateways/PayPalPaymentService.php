<?php

namespace Botble\Paypal\Services\Gateways;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Paypal\Services\Abstracts\PayPalPaymentAbstract;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayPalPaymentService extends PayPalPaymentAbstract
{
    /**
     * Make a payment
     *
     * @param Request $request
     *
     * @return mixed
     * @throws Exception
     */
    public function makePayment(Request $request)
    {
        $amount = round((float) $request->input('amount'), $this->isSupportedDecimals() ? 2 : 0);

        $data = [
            'name'     => $request->input('name'),
            'quantity' => 1,
            'price'    => $amount,
            'sku'      => null,
            'type'     => PAYPAL_PAYMENT_METHOD_NAME,
        ];

        $currency = $request->input('currency', config('plugins.payment.payment.currency'));
        $currency = strtoupper($currency);

        $queryParams = [
            'type'     => PAYPAL_PAYMENT_METHOD_NAME,
            'amount'   => $amount,
            'currency' => $currency,
            'order_id' => $request->input('order_id'),
        ];

        if ($cancelUrl = $request->input('return_url')) {
            $this->setCancelUrl($cancelUrl);
        }

        return $this
            ->setReturnUrl($request->input('callback_url') . '?' . http_build_query($queryParams))
            ->setCurrency($currency)
            ->setCustomer($request->input('address.email'))
            ->setItem($data)
            ->createPayment(trans('plugins/payment::payment.payment_description', ['order_id' => Arr::first((array) $queryParams['order_id']), 'site_url' => $request->getHost()]));
    }

    /**
     * Use this function to perform more logic after user has made a payment
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function afterMakePayment(Request $request)
    {
        $status = PaymentStatusEnum::COMPLETED;

        $chargeId = session('paypal_payment_id');

        $orderIds = (array)$request->input('order_id', []);

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount'          => $request->input('amount'),
            'currency'        => $request->input('currency'),
            'charge_id'       => $chargeId,
            'order_id'        => $orderIds,
            'customer_id'     => $request->input('customer_id'),
            'customer_type'   => $request->input('customer_type'),
            'payment_channel' => PAYPAL_PAYMENT_METHOD_NAME,
            'status'          => $status,
        ]);

        session()->forget('paypal_payment_id');

        return $chargeId;
    }
}
