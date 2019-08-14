<?php
namespace jmaloneytrevetts\bagistohubexport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use jmaloneytrevetts\bagistohubexport\OrderHub;
use Webkul\Sales\Models\Order;


class OrderHubController extends Controller
{
    // public function forceExport($orderID) {
    //     $order = Order::findOrFail($orderID);
    //     dd($order);
    // }
}

//Webkul\Sales\Models\Order::where('created_at', '>=', Carbon\Carbon::now()->subDays(30)->toDateTimeString())->get();