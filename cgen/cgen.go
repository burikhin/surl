package main

import (
	"encoding/csv"
	"flag"
	"fmt"
	"os"
	"strconv"
)

func main() {
	filename := flag.String("f", "data.csv", "Filename to be generated.")
	lines := flag.Int("l", 10, "Number of lines to be generated.")
	url := flag.String("u", "https://www.google.com/search?q=", "Link to be generated. Program will append an integer to the end of the string base on '-l' param.")
	
	flag.Parse()

	fmt.Println("filename:", *filename)
    fmt.Println("lines:", *lines)
    fmt.Println("url:", *url)

	//Removing file with the same filename so we can create a new one
	os.Remove(*filename)

	writer, file, err := createCSVWriter(*filename)
	if err != nil {
		fmt.Println("Error creating CSV writer:", err)
		return
	}
	defer file.Close()
	header := []string{"url"}
	writeCSVRecord(writer, header)

	for i := 0; i < *lines; i++ {
		record := []string{*url + strconv.Itoa(i)}
		writeCSVRecord(writer, record)
	}

	writer.Flush()
	if err := writer.Error(); err != nil {
		fmt.Println("Error flushing CSV writer:", err)
	}
}

func createCSVWriter(filename string) (*csv.Writer, *os.File, error) {
	f, err := os.Create(filename)
	if err != nil {
		return nil, nil, err
	}
	writer := csv.NewWriter(f)

	return writer, f, nil
}

func writeCSVRecord(writer *csv.Writer, record []string) {
	err := writer.Write(record)
	if err != nil {
		fmt.Println("Error writing record to CSV:", err)
	}
}
