<?php
/**
 * Created by PhpStorm.
 * User: Welkin Ni
 * Date: 2016/7/21
 * Time: 20:49
 */
class Statistics
{
    private $mysqli;
    public function __construct()
    {
        $this->mysqli = new mysqli(MySQLConfig::$db_address, MySQLConfig::$db_user,
            MySQLConfig::$db_password, MySQLConfig::$db_name);
    }

    public function getStatistics()
    {
        $data=[];
        $today=strtotime(date('Y-m-d'));
        $yesterday=$today-24*60*60;

        $today_data=$this->getPayment($today);
        $yesterday_data=$this->getPayment($yesterday);
        $data['today_payment']=$today_data['amount'];
        $data['yesterday_payment']=$yesterday_data['amount'];
        $data['today_money']=$this->getMoney($today_data['payment']);
        $data['yesterday_money']=$this->getMoney($yesterday_data['payment']);
        $data['today_newuser']=$this->getNewuser($today);
        $data['yesterday_newuser']=$this->getNewuser($yesterday);
        $data['today_newvip']=$this->getNewvip($today);
        $data['yesterday_newvip']=$this->getNewvip($yesterday);

        return json_encode($data);
    }

    private function getPayment($time)
    {
        $time1=$time+24*60*60;
        $data=[];
        $data['amount']=0;
        $query="select `plane_id`,`hotel_id`,`room_id` from `payment` 
where `create_time`>? and `create_time`<?";
        $stmt=$this->mysqli->prepare($query);
        $stmt->bind_param("ii",$time,$time1);
        $stmt->execute();
        if($data['payment']=$stmt->get_result())
            $data['amount']=$stmt->affected_rows;

        return $data;
    }

    private function getMoney($payment)
    {
        $money=0;

        if ($payment) {
            $rows=[];
            while($row = $payment->fetch_assoc()) {
                $rows[]=$row;
            }
            $plane_id=array_filter(array_column($rows,'plane_id'));

            if($plane_id)
            {
                $plane=implode(",",$plane_id);
                $plane="(".$plane.")";
                $plane_money=$this->mysqli->query("select `price` from `plane` 
where `plane_id` in $plane");
                while($row = $plane_money->fetch_assoc()) {
                    $money+=$row['price'];
                }
            }

            if($rows)
            {
                foreach($rows as $v)
                {
                    $hotel_id=$v['hotel_id'];
                    if(!$hotel_id)
                        continue;
                    $room_id=$v['room_id'];
                    $room_money=$this->mysqli->query("select `price` from `room` 
where `hotel_id`=$hotel_id and `room_id`=$room_id");
                    $row = $room_money->fetch_assoc();
                    $money+=$row['price'];
                }
            }
        }

        return $money;
    }

    private function getNewuser($time)
    {
        $time1=$time+24*60*60;
        $data=0;
        $query="select * from `user` where `create_time`>? and `create_time`<?";
        $stmt=$this->mysqli->prepare($query);
        $stmt->bind_param("ii",$time,$time1);
        $stmt->execute();
        if($stmt->get_result())
            $data=$stmt->affected_rows;

        return $data;
    }

    private function getNewvip($time)
    {
        $time1=$time+24*60*60;
        $data=0;
        $query="select * from `user` where `create_time`>? and `create_time`<? and `vip`=1";
        $stmt=$this->mysqli->prepare($query);
        $stmt->bind_param("ii",$time,$time1);
        $stmt->execute();
        if($stmt->get_result())
            $data=$stmt->affected_rows;
        return $data;
    }
}