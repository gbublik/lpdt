<?php
use GBublik\Lpdt\Writer\WriterInterface;
use GBublik\Lpdt\HandlerInterface;
use GBublik\Lpdt\LogServer;

require __DIR__.'/../vendor/autoload.php';

/**
 * Класс пользовательского обработчика.
 *
 * Предпологается что пользовательский обработчик может выполнять любую работу.
 * Реализовывать обработчики, которые могут работать с LPDT, можно двумя способами.
 *
 *      1)  В случаии если ваш  обработчик реализуется через класс, то подойдет следующее решение:
 *          В классе - реализающем логику обработчика, реализовать интерфейс GBublik\Lpdt\HandlerInterface.
 *          В интерфейсе 1 метод "execute". Метод вызывается автоматически во время запуская команды (debug или websocket),
 *          При запуске метода, в него передается объек предназначеный для работы с логом. Объект реализует
 *          интерфейс GBublik\Lpdt\Writer\WriterInterface (см. ниже).
 *
 *      2)  Отдельный файл в котором глобально будет достуен объект реализующий
 *          интерфейс GBublik\Lpdt\Writer\WriterInterface;
 *
 *
 * Интерфейс GBublik\Lpdt\Writer\WriterInterface имеет 4 метода.
 *      WriterInterface::write(string $str), // Отправляет сообщение. В случаеи debug - в консоль, в случаи websocket - подписчикам websocket сервера
 *      WriterInterface::error(string $str), // Отправляет сообщение об ошибки.
 *      WriterInterface::step(string $str) // Устанавливает текущий этап выполнения пользовательского обработчика
 *      WriterInterface::finish(string $str = null) //Финальное сообщение
 */
class MyClassHandler implements HandlerInterface
{
    /** @var WriterInterface */
    protected $logger;

    /** @var int  */
    protected $iterator = 0;

    protected $amount = 0;

    protected $amountErrors = 0;

    /**
     * Единственный метод реализующий интерфейс GBublik\Lpdt\HandlerInterface
     * Метод будет запущен во время выполнения команды. Например: debug или websocket
     *
     * @param WriterInterface $log
     */
    public function execute(WriterInterface $log)
    {
        // Буду использовать логер в любом методе класса.
        $this->logger = $log;

        // Этап скинирования файлов
        $this->logger->step('Сканироваение файлов');
        // Запуск алгоритмов пользовательского обработчика
        $this->scanDir(__DIR__ . '/../');

        $this->logger->critical('Фейковые данные', ['d' => 2, 'f' => 23]);

        // Этап обработки фейковых данных
        $this->logger->step('Фейковые данные');
        $this->fakeData();

        // Этап скинирования файлов
        $this->logger->step('Повторное сканироваение файлов');
        // Запуск алгоритмов пользовательского обработчика
        $this->scanDir(__DIR__ . '/../');

        //Финальное сообщение
        $this->logger->finish();
    }

    /**
     * Эмулирует полезную работу в виде обработкий файлов на жестком диске
     * @param string $target
     */
    protected function scanDir(string $target)
    {
        static $loading = [];
        foreach (glob( $target . '*', GLOB_MARK ) as $item) {
            $loading[] = $loading;
            $this->amount++;
            if(is_dir($item)){
                $this->scanDir($item);
            } else {
                $this->logger->debug($item);
            }
            $this->iterator++;

            $item = str_replace([__DIR__, '/.'], '', $item);

            $this->doMakeHardWork();

            if ($this->iterator % 100 === 0) {
                $this->amountErrors++;
                $this->logger->error('Ошибка в файле ' . $item);
            }
            if ($this->iterator % 50 === 0) {
                $this->logger->notice('Уведомил, значит все ок,  ' . $item);
            }
            if ($this->iterator % 40 === 0) {
                $this->logger->info('Новый файл добвлен ' . $item);
            }
            if ($this->iterator % 200 === 0) {
                $this->logger->warning('Предупреждаю, тут не все ок: ' . $item);
            }
            //if ($this->iterator > 50) break;
        }
    }

    /**
     * Эмулирует полезную работу в виде обработкий данных полученных с удаленного сервера
     */
    protected function fakeData() {
        $this->logger->debug('Запрос к удаленному серверу...');

        try {
            $result = @json_decode(@file_get_contents('http://jsonplaceholder.typicode.com/posts'), true);
        }catch (Exception $e){
            $result = [];
        }
        if (empty($result)) {
            $this->logger->error('Сервер не отдал данные');
        } else {
            $this->logger->debug(sprintf('Прилетело %d записей', count($result) ));
        }

        foreach ($result as $key => $data) {
            $this->logger->info('Обработана запись ' . $data['title'], $data);
            $this->doMakeHardWork();$this->doMakeHardWork();$this->doMakeHardWork();
            $this->doMakeHardWork();$this->doMakeHardWork();$this->doMakeHardWork();
            if ($key % 30 === 0) {
                $this->logger->alert('Предупреждаю в данных ' . $data['title'], $data);
            }
            if ($key % 50 === 0) {
                $this->logger->error('Ошибка в данных ' . $data['title'], $data);
            }
        }
    }

    /**
     * Генерирует нагрузку
     */
    protected function doMakeHardWork()
    {
        $i = 0;
        while ($i < 2000000) {
            $i++;
        }
    }
}

new LogServer(new MyClassHandler);
