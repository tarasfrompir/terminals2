import pyaudio
import wave
import sys

p = pyaudio.PyAudio()

# аргумент -devicelist - список выходных устройств
# аргумент -play nameoffile devicenumber проиграет файл(имя) на устройстве номер(1) 

if (sys.argv[1] == '-devicelist'):
	info = p.get_host_api_info_by_index(0)
	numdevices = info.get('deviceCount')
	for i in range(0, numdevices):
		if (p.get_device_info_by_host_api_device_index(0, i).get('maxOutputChannels')) > 0:
			out = i, p.get_device_info_by_host_api_device_index(0, i).get('name')
			print (str(out).encode('latin1').decode('cp1251'))

elif (sys.argv[1] == '-play' and sys.argv[2] != "" and sys.argv[3] != ""):

    CHUNK = 1024

    wf = wave.open(sys.argv[2], 'rb')

    stream = p.open(format=p.get_format_from_width(wf.getsampwidth()),
                channels=wf.getnchannels(),
                rate=wf.getframerate(),
                output=True,
                output_device_index=int(sys.argv[3]))

    data = wf.readframes(CHUNK)

    while data :
        stream.write(data)
        data = wf.readframes(CHUNK)

    stream.stop_stream()
    stream.close()

else :
	print ("неправильный аргумент")	
	print ("# аргумент -devicelist - список выходных устройств")
	print ("# аргумент -play nameoffile devicenumber проиграет файл(имя) на устройстве номер(1)")