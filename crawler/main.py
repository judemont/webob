import api
import requests
from bs4 import BeautifulSoup
import threading
import time
import random



THREADS = 10
MAX_TEXT_LENGTH = 500

def crawl(url):
    print(f"URL: {url}")
    try:
        response = requests.get(url)
        soup = BeautifulSoup(response.text, 'html.parser')
    except requests.exceptions.RequestException as e:
        url = api.getJob()
        crawl(url)

    links = []
    for a in soup.find_all('a', href=True):
        try:
            get = requests.get(a['href'])
            if get.status_code == 200:
                links.append(a['href'])
        except requests.exceptions.RequestException as e:
            continue


    title = (soup.title.string if soup.title else "No title")[:100]
    description = (soup.find('meta', attrs={'name': 'description'}).get('content', '') if soup.find('meta', attrs={'name': 'description'}) else '')[:200]
    h1 = (soup.find('h1').get_text() if soup.find('h1') else '')[:100]
    text = soup.get_text()[:MAX_TEXT_LENGTH]

    contents = [[title, 7], [description, 4], [h1, 5], [text, 2]]

    api.addSite(url, contents, links)
    url = api.getJob()
    crawl(url)

if __name__ == "__main__":
    
    print("Starting WeBoB...")

    for i in range(THREADS):
        t = threading.Thread(target=crawl, args=(api.getJob(),))
        t.start()
        time.sleep(random.uniform(0.100, 0.300))
