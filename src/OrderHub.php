<?php

namespace jmaloneytrevetts\bagistohubexport;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Models\Order;
use Webkul\Product\Models\Product;
use Webkul\Attribute\Models\Attribute;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Notification;
use jmaloneytrevetts\bagistohubexport\SlackNotification;

class OrderHub extends Model
{
    protected $table = 'orders_hub';
    protected $fillable = ['id', 'hub_id', 'post_url', 'post_data'];

    public static function forceExport($orderID)
    {
        if (static::hasBeenExported($orderID)) {
            //has been exported already
            return false;
        }

        $order = Order::findOrFail($orderID);
        $array = static::generateArrayFromOrder($order);
        $result = static::sendToHub($array);
        if (isset($result->data) && isset($result->data->id)) {
            $hubOrderID = $result->data->id;
            #echo 'hub Order ID:'.$hubOrderID.'<br>';
            static::assignHubIDtoOrderID($orderID, $hubOrderID, $array);

            //send successful slack notification
            $message = ' order #' . $orderID. ' successfully exported to Hub Order #'.$hubOrderID;
            Notification::route('slack',env('SLACK_HOOK'))->notify(new SlackNotification(true, $message));


            return $hubOrderID;
        } else {
            //send failed slack notification
            $message = ' order #' . $orderID. ' failed exporting to Hub';
            Notification::route('slack',env('SLACK_HOOK'))->notify(new SlackNotification(false, $message . (isset($result->debug)?' ' . explode("\n",$result->debug):'') ));


            #var_dump($result);
            return false;
        }
    }

    

    private static function hasBeenExported($order_id)
    {
        return OrderHub::find($order_id);
    }

    private static function assignHubIDtoOrderID($orderID, $hubOrderID, $sentData)
    {
        $ho = new OrderHub();
        $ho->hub_id = $hubOrderID;
        $ho->id = $orderID;
        $ho->post_data = json_encode($sentData);
        $ho->save();
    }

    private static function getHubItemIdAttributeId()
    {
        $attr = Attribute::where('code', 'hubItemID')->get();
        if (!count($attr)) {
            throw new Exception('hubItemID has not been set up as an attribute');
        } else {
            return $attr->first()->id;
        }
    }

    public static function getOrdersThatNeedExporting($minDate = '2019-01-01')
    {
        $log = ['Starting getOrdersThatNeedExporting'];

        

        $orders = Order::whereDate('created_at', '>=', $minDate)
            ->where('status', 'completed')
            ->with('items')
            ->get();

        if (count($orders)) {
            foreach ($orders as $order) {
                if (!static::hasBeenExported($order->id)) {
                    $log[] = 'Order #' . $order->id . ' has not been exported yet';
                    if ( static::hubItemsInOrder($order) ) {
                        $log[] = 'Order #' . $order->id . ' has hub items and should be exported';
                        $hubExportResult = static::forceExport($order->id);
                        if ($hubExportResult) {
                            $log[] = 'Order #' . $order->id . ' exported to Hub# ' . $hubExportResult;
                        } else {
                            $log[] = 'ERROR!! Order #' . $order->id . ' encountered error(s) exporting to hub! ';
                        }
                        
                    } else {
                        $log[] = 'Order #' . $order->id . ' has no hub items';
                    }
                } else {
                    $log[] = 'Order #' . $order->id . ' has been exported';
                }
            }
        } else {
            $log[] = 'Nothing to do!';
        }

        var_dump($log);

        return ['log' => $log];
    }

    private static function hubItemsInOrder($order) {
        $c=0;
        foreach ($order->items as $item) {
            if ( static::itemIsHubItem($item) ) {
                $c++;
            }
        }
        return $c;
    }

    private static function itemIsHubItem($item)
    {
        $hubItemAttributeId = static::getHubItemIdAttributeId();
        $product = Product::findOrFail($item->product_id);
        $hubAttributes = $product->attribute_values->where('attribute_id', $hubItemAttributeId);
        if (count($hubAttributes)) {
            $hubAttribute = $hubAttributes->first();
            $hubItemID = $hubAttribute->text_value;
            return $hubItemID;
        } else {
            return false;
        }
    }


    private static function generateArrayFromOrder(Order $order)
    {
        //fetch needed data
        $billing_addresses = $order->addresses->where('address_type', 'billing');
        $shipping_addresses = $order->addresses->where('address_type', 'shipping');

        $hubItemAttributeId = static::getHubItemIdAttributeId();

        $arr = [
            'api_version' => 2,
            'order_type' => 'shopping',
            'cart_number' => $order->id,
            'shipping' => $order->shipping_amount,
            'handling' => 0.00,
            'freight' => 0.00,
            'tax' =>  $order->tax_amount,
            'total' => $order->grand_total,
            'billing_address' => [
                'name' => '',
                'line1' => '',
                'line2' => '',
                'city' => '',
                'state' => '',
                'zip' => '',
                'country' => ''
            ],
            'shipping' => [
                'method_name' => $order->shipping_title,
                'hub_ship_method_id' => env('HUB_SHIP_METHOD_ID'),
                'price' => $order->shipping_amount,
                'handling' => 0.00,
                'address' => [
                    'name' => '',
                    'line1' => '',
                    'line2' => '',
                    'city' => '',
                    'state' => '',
                    'zip' => '',
                    'country' => ''
                ],
            ],
            'ordered_by' => [
                'name_first' => $order->customer_first_name,
                'name_last' => $order->customer_last_name,
                'name_complete' => $order->getCustomerFullNameAttribute(),
                'phone' => '',
                'email' => $order->customer_email,
                'ext_emp_id' => '',
                'add_emp_info' => ''
            ],
            'items' => []

        ];

        //fill in billing address
        if (count($billing_addresses)) {
            $billing_address = $billing_addresses->first();
            $arr['billing_address'] = [
                'name' => $billing_address->first_name . ' ' . $billing_address->last_name,
                'line1' => $billing_address->address1,
                'line2' => $billing_address->address2,
                'city' => $billing_address->city,
                'state' => $billing_address->state,
                'zip' => $billing_address->postcode,
                'country' => $billing_address->country
            ];
        }

        //fill in shipping address
        if (count($shipping_addresses)) {
            $shipping_address = $shipping_addresses->first();
            $arr['shipping']['address'] = [
                'name' => $shipping_address->first_name . ' ' . $shipping_address->last_name,
                'line1' => $shipping_address->address1,
                'line2' => $shipping_address->address2,
                'city' => $shipping_address->city,
                'state' => $shipping_address->state,
                'zip' => $shipping_address->postcode,
                'country' => $shipping_address->country
            ];
        }
        // echo 'in here';
        foreach ($order->items as $item) {
            // echo 'sku:'.$item->sku.'<br>';
            #dd($item);
            $product = Product::findOrFail($item->product_id);
            $hubAttributes = $product->attribute_values->where('attribute_id', $hubItemAttributeId);
            if (count($hubAttributes)) {
                $hubAttribute = $hubAttributes->first();
                $hubItemID = $hubAttribute->text_value;

                $arrItem = [
                    'hub_item_id' => $hubItemID,
                    'ref_id' => $item->id,
                    'qty' => $item->qty_ordered,
                    'price' => $item->price,
                    'attributes' => []
                ];
                $arr['items'][] = $arrItem;
            }
        }
        //if there are no items in $arr['items'], that means there are no hub items in this order
        if (!count($arr['items'])) {
            throw new Exception('There are no hub items in this order');
        }


        return $arr;
    }

    private static function sendToHub($array)
    {
        $base_url = env('HUB_ADDRESS');
        $key = env('HUB_API_KEY');

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $base_url,
            // You can set any numjber of default request options.
            'timeout'  => 30.0,
            'headers' => array(
                'apikey' => $key,
                'Content-Type' => 'application/json',
            )

        ]);

        $r = $client->request('POST', '/api/order', [
            'json' => ['api_version' => 2, 'data' => $array]
        ]);

        #echo  $r->getBody()->read(10240);

        return json_decode($r->getBody()->read(10240));
    }
}
