<?php

class PlanModel extends Model
{
    public function smartDelivery()
    {
        import('@.ORG.plan.plan_smart_delivery');
        (new plan_smart_delivery())->runTask();
    }
}
