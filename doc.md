# 使用文档和 api 文档

## 使用方法

### 1、安装扩展并正常启动

### 2、配置

默认的请求地址为：`http://127.0.0.1:22990`,

运行后在浏览器中直接打开，返回:

```bash
    ServerSentEvents
-----------------------
       workerman
```

或

```bash
       WebSocket
-----------------------
       workerman
```

如果使用域名连接，需要配置转发，一般和主网站共用域名，指定一个路径如`/sse`做转发

配置好后，求地地址可以为：`http://www.mysiete.com/sse`

如果需要使用`https`协议，那么需要配置`SSL`

配置好后，求地地址可以为：`https://www.mysiete.com/sse`

#### nginx

```bash
server
{
    #主网站配置
    listen 80;
    server_name www.mysiete.com;
    index index.php index.html;
    root /www/wwwroot/www.mysiete.com/public;

    #SSL相关配置，使用`https`协议必填

    #sse配置(仅供参考)
    location /sse {
        proxy_pass http://127.0.0.1:22990; #ip和端口根据实际情况调整
        proxy_set_header Host $host;
        proxy_set_header X-Real_IP $remote_addr;
        proxy_set_header X-Forwarded-For $remote_addr:$remote_port;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;  # 设置代理读取服务器响应的超时时间
        proxy_send_timeout 60s;
        proxy_connect_timeout 1h;  # 设置客户端连接的超时时间

        #其他配置...

        break;
    }

    #ws配置(仅供参考)
    location /sse-ws {
        proxy_pass http://127.0.0.1:22991;
        proxy_set_header Host $host;
        proxy_set_header X-Real_IP $remote_addr;
        proxy_set_header X-Forwarded-For $remote_addr:$remote_port;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;   # 升级协议头
        proxy_set_header Connection upgrade;

        proxy_read_timeout 60s;  # 设置代理读取服务器响应的超时时间
        proxy_send_timeout 60s;

        #其他配置...

        break;
    }

    #其他配置...
}
```

### 3、在后台添加应用 APP

可以添加多个应用，以实现支持多个网站的推送需求，一个网站一个 APP。
添加以后查看该应用的`app_id`和`secret`并记录。

---

### 4、接口说明

#### 管理员接口

用于管理用户，推送用户信息到推送系统。

公共验证参数:

```php
[
    'app_id' => 'app_id', //添加app以后生成的id
    'sign' => '签名', // md5($app_secret + $time)
    'time' => '时间戳', //$time
]
```

---

### 5、同步用户到推送系统

在用户使用推送功能前时，需要把此用户信息同步到推送系统中。

请求后端接口获取建立推送需要的信息。

后端请求：：`http://www.mysiete.com/api/woksseadmin/pushUser`

参数：

```php
 [
    //admin-api公共验证参数
    'app_id|app_id' => 'require',
    'sign|签名' => 'require',
    'time|时间戳' => 'require',
    //业务参数
    'uid' => '用户id',//业务系统里的用户id
    'nickname' => '昵称',
    'token' => 'token', //你系统里面的用户token
    'remark' => '备注信息',
 ]
 //token不超过100字符，过长建议md5转一下。也可留空，由系统自动生成。
```

返回：

```json
{ "code": 1, "msg": "成功", "data": { "token": "token" } }
```

如果请求时传了`token`参数，则原样返回，如果未传则自动生成。

---

### 6、用户连接

#### login：登录

前端页面使用`http`请求进行登录。

sse 关键 js 代码

```javascript
var uid = 10; //接收用户的uid
var token = "xxxxxxxxxxxxxxxxxxx"; //用户的token
var app_id = "10001"; //你的app_id

var time = Math.floor(new Date().getTime() / 1000); //当前时间戳
var sign = md5(token + time); //md5加密得sign。此处是伪代码，md5方法需要你自己引入相关js库实现

var url = "http://www.mysiete.com/sse";

var source = new EventSource(
  url + "?app_id=" + app_id + "&uid=" + uid + "&sign=" + sign + "&time=" + time
);
source.onmessage = function (msg) {
  var event = msg.event;
  var data = msg.data;
  console.log("Message successfully received");
  console.log(data);
};
source.onerror = function (event) {
  console.log("EventSource failed.");
  console.log(event);
};
```

sse 关键 js 代码

```javascript
var uid = 10; //接收用户的uid
var token = "xxxxxxxxxxxxxxxxxxx"; //用户的token
var app_id = "10001"; //你的app_id
var url = "ws://www.mysiete.com/sse-ws";

var socket = null;
var isOpen = false;
var reonnecTimmer = null;

function connect() {
  // 创建一个新的 WebSocket 连接
  socket = new WebSocket(url);

  socket.addEventListener("open", function (event) {
    var time = Math.floor(new Date().getTime() / 1000); //当前时间戳
    var sign = md5(token + time); //md5加密得sign。此处是伪代码，md5方法需要你自己实现

    var data = { app_id, uid, sign, time };
    socket.send(JSON.stringify(data));
    isOpen = true;
  });

  // 监听消息事件
  socket.addEventListener("message", function (msg) {
    console.log("Message successfully received");
    var data = msg.data;
    console.log(data);
  });

  // 监听错误事件
  socket.addEventListener("error", function (event) {
    console.error("WebSocket failed.");
    console.log(event);
  });

  // 连接关闭时的回调
  socket.addEventListener("close", function (event) {
    console.log("WebSocket is closed now.");
    isOpen = true;
    if (reonnecTimmer) {
      clearTimeout(reonnecTimmer);
    }
    reonnecTimmer = setTimeout(function () {
      console.log("重新连接");
      connect();
    }, 5000);
  });
}

connect(); //开始连接

//保持连接，每50秒发一次消息
setInterval(function () {
  if (isOpen) {
    socket.send("ping");
  }
}, 50 * 1000);
```

连接成功后，浏览器控制台打印。

```json
{ "code": 1, "msg": "登录成功", "event": "login_succeed" }
```

#### 发送推送消息

用户连接成功后，就可以发送推送消息了。

后端请求：`http://www.mysiete.com/api/woksseadmin/pushMsg`

参数：

```php
//admin-api公共验证参数
'app_id|app_id' => 'require',
'sign|签名' => 'require',
'time|时间戳' => 'require',
//业务参数
'uid|接收用户uid' => 'require',//接收用户的uid 如：10，可以多个如：10,11
'data|数据' => 'require|string',// 如：json_encode(['event' => 'new_order', 'num' => 10])
```

返回:

```json
{ "code": 1, "msg": "done" }
```

msg 为 done 表示推送成功，为 fialed 表示推送失败(用户未在线等原因)

```php
public function push()
{
    $appSecret = 'xxxxxxxxxxxxxxxx'; //后台查看
    $app_id = 10001;//应用id
    $time = time();
    $sign = md5($appSecret . $time);

    $data = [
        'app_id' => $app_id,
        'sign' => $sign,
        'time' => $time,
        'uid' => 100,//接收用户id
        'data' => json_encode(['event' => 'new_order', 'num' => 10]),
    ];

    $client = new Client();

    $response = $client->request('POST', 'https://www.mysiete.com/api/woksseadmin/pushmsg', [
        'form_params' => $data,
    ]);
}
```
