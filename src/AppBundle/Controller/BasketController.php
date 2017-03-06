<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class BasketController extends BaseController
{
    /**
     * @Route("/bdhandlers/basket.php", name="basket")
     */
    public function indexAction(Request $request)
    {
        $action = $request->request->get('action');
        
        $result = [];
        
        $this->initCart();
        
        if('setDeliveryPrice' === $action) {
            $result = $this->setDeliveryPrice($request);
        }
        
        if('addToBasket' === $action) {
            $result = $this->addToBasket($request);
        }
        
        if('getBasket' === $action) {
            $result = $this->getBasket($request);
        }
        
        if('updateCount' === $action) {
            $result = $this->updateCount($request);
        }
        
        if('delete' === $action) {
            $result = $this->delete($request);
        }
        
        return new JsonResponse($result);
        
//        return $this->render('default/index.html.twig', $params);
    }
    
    protected function setDeliveryPrice(Request $request)
    {
        // action = setDeliveryPrice
        // IN:
        // free: 30
        // price: 1.5
        // 
        // OUT: 
        // total: 13.82
        // delivery_price: "1.50"

        
       return ['total' => 0, 'delivery_price' => "0"];
    }
    
    protected function addToBasket(Request $request)
    {
        /*
//        for items
//
//        item_id
//        count
//        category_id
//        category_name
//        item_name
//        item_img
//        item_price
//        item_old_price
//        is_gift
//        min_total
//        half_enabled
//        uniqueId
         * */
        $id = $request->request->get('item')['item_id'];
        $countt = $request->request->get('item')['count'];
        $product = $this->getRepo('Product')->find($id);
        
        if (!$product) {
            return 'no product';
        }

        $cart = new Session();
        
        $items = $cart->get('basket')['basket'];
        
        $item = $product->getForCart();
        $item['count']   = $countt + $this->getOldCount($id); // + old item count
        $item['half']    = null;
        $item['is_gift'] = 0;

        $items[$product->getId()] = $item;
        $cart->set('basket', ['basket' => $items]);
        
        $this->recalculateTotal();
        
        $cart->set('basket', [
            'basket' => $items, 
            'total' => $cart->get('total')
                ]);

        return [
            'total' => $cart->get('total'),
            $cart->get('basket'),
            ];
    }
    
    protected function getOldCount($id)
    {
        $cart = new Session();
        $items = $cart->get('basket')['basket'];
        
//        var_dump($items);die();
        
        if(!isset($items[$id])) {
            return 0;
        }
        
        return $items[$id]['count'];
    }
    
    protected function updateCount(Request $request)
    {
        $countt = $request->request->get('count');
        $id = $request->request->get('item_id');
        
        $cart = new Session();
        
        $items = $cart->get('basket')['basket'];
        
        $items[$id]['count'] = $countt;
        
        $cart->set('basket', ['basket' => $items]);
        
        $this->recalculateTotal();
        
        return $cart->get('total');
    }
    
    protected function delete(Request $request)
    {
        
    }

    protected function getBasket(Request $request)
    {
        $cart = new Session();
        return $cart->get('basket');
    }
    
    protected function initCart()
    {
        $cart = new Session();
        
        if (
//                1 === count($cart->get('basket')) or 
            !$cart->has('calling')) {
            $cart->set('calling', 1);
            
            $auxiliary = $this->getRepo('Product')->findByProposeInCart(true);
            
            $items = [];
            foreach($auxiliary as $one) {
                $item = $one->getAuxiliaryForCart();
                $item['count'] = 0;
                $item['half'] = null;
                $item['is_gift'] = 0;

                $items['add_' . $one->getId()] = $item;
            }
            
            $cart->set('basket', ['basket' => $items]);
        }
        
        $cart->set('calling', $cart->get('calling')+1);
    }
    
    protected function recalculateTotal()
    {
        $cart = new Session();
        $items = $cart->get('basket')['basket'];
        $total = 0;
        foreach($items as $item) {
            $total += $item['item_price'] * $item['count'];
        }
        
        $cart->set('total', $total);
    }
}