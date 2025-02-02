package main

import (
	"database/sql"
	"encoding/csv"
	"flag"
	"fmt"
	"io"
	"log"
	"math"
	"net/url"
	"os"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/joho/godotenv"
	_ "github.com/lib/pq"
)

const (
	batchSize = 5001
)

var (
	alphabet = []byte("123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ")
)

func main() {
	startTime := time.Now()

	db := getDbConnection()
	defer db.Close()

	filename := flag.String("f", "data.csv", "Filename to read for import.")
	flag.Parse()
	fmt.Println("filename:", *filename)

	f, err := os.Open(*filename)
	if err != nil {
		log.Fatal(err)
	}
	defer f.Close()

	csvReader := csv.NewReader(f)

	currentCount := 0
	currentBatch := [batchSize]map[string]string{}
	readHeader := true
	header := []string{}

	var wg sync.WaitGroup
	limit := make(chan bool, 20)

	for {
		row, err := csvReader.Read()
		if err == io.EOF {
			break
		}
		if err != nil {
			log.Fatal(err)
		}
		if readHeader {
			readHeader = false
			header = row
			continue
		}

		surl := make(map[string]string)
		for k, v := range row {
			surl[header[k]] = strings.Trim(v, " ")
		}

		if u, ok := surl["url"]; ok {
			if _, err := url.ParseRequestURI(u); err == nil {
				currentBatch[currentCount] = surl
				currentCount++

				if currentCount >= batchSize {
					wg.Add(1)
					limit <- true
					go processUrlBatch(currentBatch, currentCount, db, limit, &wg)

					currentCount = 0
					currentBatch = [batchSize]map[string]string{}
				}
			}
		}
	}
	if currentCount > 0 {
		wg.Add(1)
		limit <- true
		go processUrlBatch(currentBatch, currentCount, db, limit, &wg)
	}

	wg.Wait()

	endTime := time.Now()
	executionTime := endTime.Sub(startTime)
	fmt.Println("Execution time: ", executionTime)
}

func processUrlBatch(batch [batchSize]map[string]string, count int, db *sql.DB, limit chan bool, wg *sync.WaitGroup) {
	insertS := "INSERT INTO surls (url, created_at, updated_at) VALUES "

	for i := 0; i < count; i++ {
		insertS += "(" + "'" + batch[i]["url"] + "'" + ", current_timestamp, current_timestamp" + "), "
	}

	//Removing the last coma and space from generated string
	insertS = insertS[:len(insertS)-2]
	insertS += "ON CONFLICT DO NOTHING RETURNING id, url"

	rows, err := db.Query(insertS)
	if err != nil {
		panic(err.Error())
	}

	defer rows.Close()

	updateS := "INSERT INTO surls (id, url, token) VALUES "

	rowsFound := false
	for rows.Next() {
		rowsFound = true
		var id int
		var url string
		err = rows.Scan(&id, &url)
		if err != nil {
			panic(err.Error())
		}

		token := base58Encode(id)
		updateS += "(" + strconv.Itoa(id) + "," + "'" + url + "'" + "," + "'" + token + "'" + "), "
	}

	//Checking for errors during iteration
	err = rows.Err()
	if err != nil {
		panic(err.Error())
	}

	if rowsFound {
		//Removing the last coma and space from generated string
		updateS = updateS[:len(updateS)-2]
		updateS += " ON CONFLICT (id) DO UPDATE SET " +
			"token=EXCLUDED.token, " +
			"url=surls.url, " +
			"created_at=surls.created_at, " +
			"updated_at=surls.updated_at"

		_, err = db.Exec(updateS)
		if err != nil {
			panic(err.Error())
		}
	}

	<-limit
	defer wg.Done()
}

func base58Encode(value int) string {
	encoded := ""

	v := value
	for v > 0 {
		remainder := v % 58
		v = int(math.Floor(float64(v) / 58))
		encoded = string(rune(alphabet[remainder])) + encoded
	}

	return encoded
}

func getDbConnection() *sql.DB {
	err := godotenv.Load("../.env")
	if err != nil {
		log.Fatal("Error loading .env file")
	}

	portEnv, err := strconv.Atoi(os.Getenv("DB_PORT"))
	if err != nil {
		panic(err.Error())
	}
	hostEnv := os.Getenv("DB_HOST")
	databaseEnv := os.Getenv("DB_DATABASE")
	usernameEnv := os.Getenv("DB_USERNAME")
	passwordEnv := os.Getenv("DB_PASSWORD")

	pgConnectionString := fmt.Sprintf(
		"host=%s port=%d user=%s password=%s dbname=%s sslmode=disable",
		hostEnv, portEnv, usernameEnv, passwordEnv, databaseEnv)
	db, err := sql.Open("postgres", pgConnectionString)
	// db.SetMaxOpenConns(20)
	// db.SetMaxIdleConns(0)
	// db.SetConnMaxLifetime(time.Nanosecond)
	if err != nil {
		panic(err.Error())
	}

	err = db.Ping()
	if err != nil {
		panic(err.Error())
	}

	return db
}
