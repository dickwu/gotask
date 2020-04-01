package main

import (
	"github.com/reasno/gotask/pkg/config"
	"log"
)

func main() {
	addr, err := config.Get("gotask.socket_address", "default")
	if err != nil {
		log.Fatalln(err)
	}
	log.Println(addr)
	addr, err = config.Get("gotask.non_exist", "default")
	if err != nil {
		log.Fatalln(err)
	}
	log.Println(addr)
	err = config.Set("gotask.non_exist", "exist")
	if err != nil {
		log.Fatalln(err)
	}
	addr, err = config.Get("gotask.non_exist", "")
	if err != nil {
		log.Fatalln(err)
	}
	log.Println(addr)
	has, err := config.Has("gotask.non_exist")
	if err != nil {
		log.Fatalln(err)
	}
	log.Println(has)
}