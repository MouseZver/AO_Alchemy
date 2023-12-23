#define MOUSE_BUTTON_LEFT_CLICK 'L'
#define MOUSE_BUTTON_RIGHT_CLICK 'R'
#define MOUSE_BUTTON_LEFT_DOWN '2'
#define MOUSE_BUTTON_RIGHT_DOWN '4'
#define MOUSE_BUTTON_LEFT_UP '1'
#define MOUSE_BUTTON_RIGHT_UP '3'
#define MOUSE_BUTTONS_RESET '0'

#define DELAY_CLICK 50
#define __WAIT__ 50
#define PIN_D12 12

void setup()
{
	Serial.begin(9600);

	Serial.println("Hello! 🙂");

	pinMode(2, OUTPUT);// LEFT_CLICK
	pinMode(3, OUTPUT);// RIGHT_CLICK
	pinMode(4, OUTPUT);
  pinMode( PIN_D12, OUTPUT );

	digitalWrite(2, 1);
	digitalWrite(3, 1);
	digitalWrite(4, 0);
}

void loop()
{
	digitalWrite( PIN_D12, ! digitalRead( PIN_D12 ) );
  
  if (Serial.available() > 0)
	{
		digitalWrite(LED_BUILTIN, HIGH);

		char c = Serial.read();
		mouseAction(c);
		
		digitalWrite(LED_BUILTIN, LOW);
		delay(10);
	}
}

void mouseAction(char c)
{
	switch (c)
	{
		case MOUSE_BUTTON_LEFT_CLICK: // Клик левой кнопкой
			digitalWrite(2, 0);
			delay(DELAY_CLICK);
			digitalWrite(2, 1);
			Serial.println("MOUSE_BUTTON_LEFT_CLICK");
			break;
		case MOUSE_BUTTON_RIGHT_CLICK: // Клик правой кнопкой
			digitalWrite(3, 0);
			delay(DELAY_CLICK);
			digitalWrite(3, 1);
			Serial.println("MOUSE_BUTTON_RIGHT_CLICK");
			break;
		case MOUSE_BUTTONS_RESET: // Сброс состояний левой/правой кнопки
			digitalWrite(2, 1);
			digitalWrite(3, 1);
			Serial.println("MOUSE_BUTTONS_RESET");
			break;
		case MOUSE_BUTTON_LEFT_DOWN: // Удерживать левую кнопку
			digitalWrite(2, 0);
			Serial.println("MOUSE_BUTTON_LEFT_DOWN");
			break;
		case MOUSE_BUTTON_LEFT_UP: // Отпустить левую кнопку
			digitalWrite(2, 1);
			Serial.println("MOUSE_BUTTON_LEFT_UP");
			break;
		case MOUSE_BUTTON_RIGHT_DOWN: // Удерживать правую кнопку
			digitalWrite(3, 0);
			Serial.println("MOUSE_BUTTON_RIGHT_DOWN");
			break;
		case MOUSE_BUTTON_RIGHT_UP: // Отпустить правую кнопку
			digitalWrite(3, 1);
			Serial.println("MOUSE_BUTTON_RIGHT_UP");
			break;
		default:
			// игнорировать CR+LF
			// if (byte(c) != 13 && byte(c) != 10){}
			// либо переключить монитор порта "Нет конца строки"
			Serial.println(c);
			digitalWrite(4, 1);
			delay(__WAIT__);
			digitalWrite(4, 0);
	}
}