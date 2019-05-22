# terminals2

/* Специфика:
Использование фреймворка pyxbmct
Если следующий звуковой файл приходит в то время, когда предыдущий еще не окончился - проигрывание предыдущего прекращается и начинается воспроизведение последнего "прибывшего" звука

Примеры вызова:
Вывод звука из файла на локальном диске:
http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["D:\\ringtone.wav"]},"id":1}

Вывод звука по http ссылке:
http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["http://192.168.1.2/cms/cached/voice/aebd42dddcca11fa8b8d5ad4d75793d3_google.wav"]},"id":1}

http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["http://192.168.1.2/cms/cached/voice/rh_e4768dae4160a3eb9a57713580eff5e6.wav"]},"id":1}

Вывод стандартного звука плагина:
http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["welcome"]},"id":1}
http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["ringtone"]},"id":1}
Возможные значения: welcome, ringtone, incall, callend, batlow, Sincall, Eincall, Sbatlow, Ebatlow, STOP
STOP - остановить текущее воспроизведение


Вывод сообщения:
http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["MESSAGE", "SmartHome Alice", "http://192.168.1.2/img/logo_small.png", "Проверка подключения"]},"id":1}

http://xbmc:xbmc@192.168.1.51:8080/jsonrpc?request={"jsonrpc":"2.0","method":"Addons.ExecuteAddon","params":{"addonid":"script.alicevox.master","params":["MESSAGE", "SmartHome Alice", "Проверка подключения", "7", "mdm"]},"id":1}
где:
7=длительность показа сообщения в секундах
mdm=стандартная картинка, вместо "mdm" может быть url ссылка на файл, например "http://192.168.1.2/img/logo.png"
 
где:
xbmc:xbmc - логин и пароль к KODI
192.168.1.51 - KODI
192.168.1.2 - удаленный сервер с хостингом картинок (например MDM) */
