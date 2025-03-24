<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:export-data-csv',
    description: 'Exports table data to a cvs file',
)]

class ExportDataToCsvCommand extends Command
{
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tableName', InputArgument::OPTIONAL, 'Name of the table to be expported.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tableName = $input->getArgument('tableName');

        if ($tableName) {
            $io->note(sprintf('Table Name: %s', $tableName));
        }

        // Database connection
        $connection = $this->manager->getConnection();

        try {
            $query = $connection->prepare("SELECT * FROM $tableName");
            $result = $query->execute();
            $data = $result->fetchAllAssociative();

            //check if result is empty
            if (empty($data)) {
                $io->warning("No data found in table: $tableName");
                return Command::FAILURE;
            }

            // path of a cvs file
            $csvFile = sys_get_temp_dir() . "/{$tableName}_export_" . date('YmdHis') . ".csv";

            // create and  open file with write permission
            $file = fopen($csvFile, 'w');

            if (!$file) {
                $io->error("file create error '$file': " . $e->getMessage());
                return Command::FAILURE;
            }

            // writing the cloumn names
            fputcsv($file, array_keys($data[0]));

            // write the row
            foreach($data as $row){ 
                fputcsv($file, $row);
            }

            fclose($file);

            $io->success('File saved.');

            // outputfile path
            $output->writeln($csvFile);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Error exporting table '$tableName': " . $e->getMessage());
            return Command::FAILURE;
        }
       

       
        
    }
}
