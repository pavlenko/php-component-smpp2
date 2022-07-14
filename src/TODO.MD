# TODO 1

optimize server/client class methods for valid algorithm

# TODO 2

- Bridge for allow to communicate app (server/client)
  with daemon
- Sequence number must be unique per connection
- Increment sequence number on each command send call
- Create common command class instead of class per type
- Add data coding converters
- Maybe can create session class for store sent packets
  and sequence number, alo maybe use sub-packets for
  allow to send chunked messages
- reconnect logic

```php
<?php
//TODO try check status if read more than exists
$bytes_to_read = 2048;
while ($chunk = fread($connection, $bytes_to_read)) {
  $response .= $chunk;
  $status = socket_get_status ($connection);
  $bytes_to_read = $status["unread_bytes"];
}
 ```
