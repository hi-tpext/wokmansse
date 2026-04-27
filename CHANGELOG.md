# CHANGELOG

---

## [1.0.2]

### 新增用户分组

- 新增用户分组字段，默认值为`default`

- 支持按用户分组发送消息

### ServerSentEvents 连接支持cookie

新增配置项 [withCredentials] 配置是否携带cookie。首次登录成功后携带cookie，网络抖动自动重连时使用cookie验证
