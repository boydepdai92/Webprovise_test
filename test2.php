<?php

function callApi(string $url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    return !empty($response) ? json_decode($response, true) : [];
}

function calculateCompanyPriceFromTravel(array $travels): array
{
    $result = [];

    foreach ($travels as $travel) {
        if (isset($result[$travel['companyId']])) {
            $result[$travel['companyId']] += $travel['price'];
        } else {
            $result[$travel['companyId']] = $travel['price'];
        }
    }

    return $result;
}

function getSumChildrenCost(&$data, $companyPrices)
{
    for ($i = 0; $i < count($data); $i++) {
        for ($j = $i + 1; $j < count($data); $j++) {
            if ($data[$i]['id'] == $data[$j]['parentId']) {
                if (isset($data[$i]['sum'])) {
                    $data[$i]['sum'] += $companyPrices[$data[$j]['id']];
                } else {
                    $data[$i]['sum'] = $companyPrices[$data[$j]['id']];
                }
            }
        }
    }
}

function transformResult(array $companies, array $companyPrices, string $parentId = "0"): array
{
    $result = [];

    foreach ($companies as $company) {
        if ($company['parentId'] == $parentId) {
            if (!empty($company['sum'])) {
                $company['cost'] = $companyPrices[$company['id']] + $company['sum'];
            } else {
                $company['cost'] = $companyPrices[$company['id']];
            }

            $company['children'] = transformResult($companies, $companyPrices, $company['id']);

            unset($company['createdAt']);
            unset($company['parentId']);
            unset($company['sum']);

            $result[] = $company;
        }
    }

    return $result;
}

class Travel
{
    private $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
    private $travels;

    public function __construct()
    {
        $this->travels = callApi($this->url);
    }

    public function getTravels()
    {
        return $this->travels;
    }
}

class Company
{
    private $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
    private $companies;

    public function __construct()
    {
        $this->companies = callApi($this->url);
    }

    public function getCompanies()
    {
        return $this->companies;
    }
}


class TestScript
{
    public function execute()
    {
        $start = microtime(true);
        $travel = new Travel();
        $company = new Company();

        $travels = $travel->getTravels();
        $companies = $company->getCompanies();

        if (empty($travels) || empty($companies)) {
            echo "Empty data travels or companies to show data";
            die;
        }

        $companyPrices = calculateCompanyPriceFromTravel($travels);
        getSumChildrenCost($companies, $companyPrices);

        $result = transformResult($companies, $companyPrices);
        echo json_encode($result);

        echo 'Total time: '.  (microtime(true) - $start);
    }
}

(new TestScript())->execute();