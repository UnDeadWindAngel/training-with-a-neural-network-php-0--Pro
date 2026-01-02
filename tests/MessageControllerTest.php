<?php

use PHPUnit\Framework\TestCase;
use src\Controllers\MessageController;
use src\Services\MessageService;

class MessageControllerTest extends TestCase
{
    private $messageService;
    private $controller;

    protected function setUp(): void
    {
        // Создаем мок сервиса
        $this->messageService = $this->createMock(MessageService::class);
        // Передаем мок в контроллер
        $this->controller = new MessageController($this->messageService);

        // Очищаем глобальные массивы перед каждым тестом
        $_POST = [];
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }


    /**
     * Тест успешного создания сообщения
     * @runInSeparateProcess
     */
    public function testCreateSuccess()
    {
        // Имитируем данные формы
        $_POST['name'] = 'Иван';
        $_POST['message'] = 'Привет всем';

        // Ожидаем, что метод сервиса будет вызван ровно 1 раз с этими параметрами
        $this->messageService->expects($this->once())
            ->method('createMessage')
            ->with('Иван', 'Привет всем', '127.0.0.1');

        // Используем перехват вывода, так как метод может что-то выводить или отправлять заголовки
        // Внимание: 'exit' в коде прервет выполнение теста.
        // Если вы не можете изменить код на 'return', добавьте @runInSeparateProcess

        try {
            $this->controller->create();
        } catch (\Throwable $e) {
            // Если в коде стоит exit, тест может упасть.
            // В профессиональной разработке вместо exit используют return или выбрасывают исключение.
        }

        // Проверяем сессию
        $this->assertEquals('Сообщение добавлено', $_SESSION['flash_message']);
        $this->assertEquals('success', $_SESSION['flash_type']);
    }

    /**
     * Тест ошибки при пустых полях
     * @runInSeparateProcess
     */
    public function testCreateValidationError()
    {
        $_POST['name'] = ''; // Пустое имя
        $_POST['message'] = '';

        // Сервис НЕ должен вызываться
        $this->messageService->expects($this->never())
            ->method('createMessage');

        try {
            $this->controller->create();
        } catch (\Throwable $e) {}

        $this->assertEquals('Заполните все поля', $_SESSION['flash_message']);
        $this->assertEquals('error', $_SESSION['flash_type']);
    }
}