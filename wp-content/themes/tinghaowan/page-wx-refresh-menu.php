<?php

$weixin = new WeixinAPI();
echo json_encode($weixin->refresh_menu());
