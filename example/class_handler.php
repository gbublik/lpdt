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
 *          В интфесе 1 метод "execute". Метод вызывается автоматически во время запуская команды (debug или websocket),
 *          При запуске метода , в него передается объек предназначеный для работы с логом. Объект реализует
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

        // Точка входа в пользовательский обработчки
        $this->scanDir(__DIR__ . '/../');

        //Финальное сообщение
        $this->logger->finish(sprintf('Обработано %d файлов , %d файла c ошибками', $this->amount, $this->amountErrors));
    }

    /**
     * Эмулирует полезную работу, в виде обработкий файлов
     * @param string $target
     */
    protected function scanDir(string $target)
    {
        foreach (glob( $target . '*', GLOB_MARK ) as $item) {
            $this->amount++;
            if(is_dir($item)){
                $this->scanDir($item);
            }
            $this->iterator++;

            $item = str_replace([__DIR__, '/.'], '', $item);

            $this->logger->write('Обработка файла: ' . $item);

            if ($this->iterator % 20 === 0) {
                $this->amountErrors++;
                $this->logger->error('Ошибка в файле ' . $item);
            }

            $this->pause();
        }
    }

    protected function pause()
    {
        $i = 0;
        while ($i < 2000000) {
            $i++;
        }
    }
}

new LogServer(new MyClassHandler);
