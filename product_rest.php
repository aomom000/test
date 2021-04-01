<?php 
    session_start();
?>
<?php
    include_once("class.db.php");
    if($_SERVER["REQUEST_METHOD"]=='GET'){
        echo json_encode(product_list(),JSON_UNESCAPED_UNICODE);
    }else if($_SERVER["REQUEST_METHOD"]=='POST'){
        echo json_encode(print_r(open_bill()));


    }
    function product_list(){
        $db = new database();
        $db->connect();
        $sql = "SELECT Product_id,Product_code,Product_Name,
                        brand.Brand_name, unit.Unit_name,
                        product.Cost, product.Stock_Quantity
        FROM   product,brand,unit
        WHERE  product.Brand_ID = brand.Brand_id 
        and    product.Unit_ID = unit.Unit_id"; 
        $result = $db->query($sql);
        $db->close();
        return $result;
    }

    function open_bill(){
        //1. check have some opnebill? 
        //1a no: create new open_bill
        //1b yes: check status openbill = 1
        //    1.2.1 yes:
        //          1.2.1a check product id exist yes: update qty in bill_detail
        //          1.2.2b check product id exist on: add product to bill_detail
        //    1.2.2 no:  
        $step=1; $bill_id=1; $bill_head=""; $bill_detail="";

        $db = new database();
        $db->connect();
        $sql = "SELECT Bill_id,Bill_Status FROM bill WHERE Cus_Id='{$_SESSION['cus_id']}' order by Bill_id desc limit 1";
        $bill_result = $db->query($sql);
        $p_id = $_POST['p_id'];
        $p_qty = $_POST['p_qty'];
        $p_price = $_POST['p_price'];
        if(sizeof($bill_result)==0){
           //insert into
           $sql = "INSERT INTO bill(Bill_id, Cus_ID, Bill_Status) VALUES (1,{$_SESSION['cus_id']},0)";
           $result = $db->exec($sql);
           $sql = "INSERT INTO bill_detail(Bill_id, Product_ID, Quantity, Unit_Price) 
                    VALUES (1,'{$p_id}','{$p_qty}','{$p_price}')";
           $result = $db->exec($sql);
        }else{
            //check [0][0] billid 
            //      [0][1] bill status
            $step="3:add new item";
                if($bill_result[0][1]==0){
                    //add new product
                    $sql = "INSERT INTO bill_detail(Bill_id, Product_ID, Quantity, Unit_Price) 
                            VALUES ({$bill_result[0][0]},{$p_id},{$p_qty},{$p_price})";
                    $result = $db->exec($sql);
                    if($result ==0){
                        $step="4:update item";
                        $bill_id = $bill_result[0][0];
                        $sql2 = "UPDATE bill_detail SET Quantity={$p_qty},Unit_Price={$p_price} WHERE Bill_id={$bill_id} and Product_ID={$p_id}";
                        $result = $db->exec($sql2);
                        $step="5:update complete";
                    }
                    $sql = "Select * from bill where Bill_Id={$bill_result[0][0]}";
                    $bill_head = $db->query($sql);
                    $sql = "Select * from bill_detail where Bill_Id={$bill_result[0][0]}";
                    $bill_detail = $db->query($sql);
                }
            }
    #INSERT INTO `bill`(`Bill_id`, `Cus_ID`, `Bill_Status`) VALUES (1,1,1)
    return ["step"=>$step, "bill"=>json_encode($bill_head), "bill_detail"=>$bill_detail];
    }
    
?>