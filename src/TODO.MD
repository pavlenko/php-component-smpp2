# TODO

- Sequence number must be unique per connection
- Increment sequence number on each command send call
- Create common command class instead of class per type
- Connection must support both client and server
- Add data coding converters
- Connection must work with stream in low level
  not work with PDU directly 
- Maybe can create session class for store sent packets
  and sequence number, alo maybe use sub-packets for
  allow to send chunked messages
