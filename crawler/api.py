import requests
import json
from secrets import API_PASSWORD
import time

API_BASE_URL = "https://webob.futureofthe.tech/api"

def getJob():
    response = requests.get(f"{API_BASE_URL}/getJob.php")   
    data = response.json()  

    job = data["next"]

    return job


def addSite(url, contents, links) :
    contentsJson = json.dumps(contents)
    linksJson = json.dumps(links)

    data = {
        "password": API_PASSWORD,
        "url": url,
        "contents": contentsJson,
        "links": linksJson
    }

    requests.post(f"{API_BASE_URL}/addSite.php", data=data)
    
    