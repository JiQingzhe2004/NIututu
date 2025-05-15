
const data = {
  
}

/*
  A类地址：10.0.0.0–10.255.255.255
  B类地址：172.16.0.0–172.31.255.255 
  C类地址：192.168.0.0–192.168.255.255
*/
function internalNet(ip) {
  if (ip.startsWith('10.')) {
    return true;
  }
  if (ip.startsWith('172.')) {
    const second = parseInt(ip.split('.')[1]);
    if (second >= 16 && second <= 31) {
      return true;
    }
  }
  if (ip.startsWith('192.168.')) {
    return true;
  }
  return false;
}

function getKey(ip) {
  const isInternalNet = internalNet(ip);
  return isInternalNet ? 'internal' : ip;
}

function registerUser(ip, socket) {
  const key = getKey(ip);
  const room = data[key]
  if (!room) {
    data[key] = []
  }
  //id：2位秒数（不够用0补齐）+3位毫秒（不够用0补齐）+随机2数
  let id = `${Math.floor(Math.random() * 1000000).toString().substring(3,5).padStart(2, '0')}${(new Date()).getMilliseconds().toString().padStart(3, '0')}`
  while (data[id]) {
    id = `${Math.floor(Math.random() * 1000000).toString().substring(3,5).padStart(2, '0')}${(new Date()).getMilliseconds().toString().padStart(3, '0')}`
  }
  data[key].push({ id, socket, targets: {} })
  return id;
}

function unregisterUser(ip, id) {
  const key = getKey(ip);
  const room = data[key]
  if (room) {
    const index = room.findIndex(user => user.id === id)
    if (index !== -1) {
      return room.splice(index, 1)
    }
  }
}

function getUserList(ip) {
  const key = getKey(ip);
  const room = data[key]
  // 去掉socket属性
  return room ?? []
}

function getUser(ip, uid) {
  const key = getKey(ip);
  const room = data[key]
  return room.find(user => user.id === uid)
}

module.exports = { registerUser, unregisterUser, getUserList, getUser }