# TODO

- Bridge for allow to communicate app (server/client)
  with daemon
- Sequence number must be unique per connection
- Increment sequence number on each command send call
- Create common command class instead of class per type
- Add data coding converters
- Maybe can create session class for store sent packets
  and sequence number, alo maybe use sub-packets for
  allow to send chunked messages
