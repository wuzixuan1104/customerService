# 客服信箱
## 簡介
大多數電子商務網站中都設有客服信箱，但是對於我這種很懶惰又討厭客訴的人，若網站使用者體驗不佳還要先了解複雜的客訴流程，我希望透過大家最常溝通的管道"line"來進行整個客訴的流程，即能方便簡單的達到目的。

本專案除了使用lineBot, 還結合了目前盛行的Trello管理專案工具當作廠商的後台。

目前問題種類簡單分為：檢舉配送品質、退貨問題、貨到缺件反應、付款問題、換貨問題、維修保固問題、發票問題、我要申訴。
不同種類的問題可以分別指派給不同群的客服專員回答，當有人提出該分類的問題，便會發送通知給該專員回答，而每個問題都會有時間提醒，問題會依照時間有不同的標籤顏色警示。

## 示意圖
+ trello客服後台
<img src='assets/img/trello1.png' width='800'>


## 使用工具：
+ Trello Api: https://trello.readme.io/docs/get-started
+ Linebot: https://developers.line.me/en/docs/messaging-api/overview/
