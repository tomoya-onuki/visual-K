<?php
if (!$_GET['file_name']) {
  echo "ファイル名の取得エラー";
  exit();
}
// ファイル名をゲームのタイトルだけ抽出
// IN  | "./data/hogehoge.csv"
// OUT | [0] => "."  [1]=> "data" [2]=>  "20190102_Brisben.csv"
$game_title = explode("/", $_GET['file_name']);
// IN  | "20190102_Brisben.csv"
// OUT | [0] => "20190102_Brisben" [1] => "csv"
$game_title = explode(".", $game_title[count($game_title) - 1]);
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" href="demo.css">
  <title> <?php echo $game_title[0]; ?> </title>
</head>

<body>
  <h2><?php echo $game_title[0]; ?></h2>
  <p id="match"> </p>
  <p>
    <?php echo '<a target="_blank" href="./view_csv.php?link=' . $_GET['file_name'] . '">csvデータを見る</a>' . "\n"; ?> / <a href="./">TOPページへ</a>
  </p>
  <p>
    <div id="scale_slider_value">10</div>
    <input id="scale_slider" type="range" name="scale" step="1" min="1" max="10" value="10">
  </p>
  <div id="container" style="height:50%; width:50%"></div>
  <script type="text/javascript">
    var cvs = document.createElement("canvas");

    // Canvas のサイズをクライアントサイズに合わせる
    cvs.width = document.documentElement.clientWidth - 10;
    cvs.height = document.documentElement.clientHeight - 10;
    // cvs.width = 950;
    // cvs.height = 950;

    document.getElementById("container").appendChild(cvs);
    var ctx = cvs.getContext("2d");

    // CSVのデータを格納する各種変数
    var data = [];
    var WinLose = [];
    var FirstSecond = [];
    var course = [];
    var speed = [];
    var side = [];
    var player = [];
    var ScoreServer = [];
    var ScoreReturner = [];
    var scale = 10;


    // sets.fill(0);
    // console.log(sets);

    function contents(side, course, speed, player) {
      var me = this;
      me.side = side;
      me.course = course;
      me.speed = speed;
      me.player = player;
      // hsv から rgb　へ変換する関数
      me.hsv2rgb = function(h, s, v) {
        var r, g, b;
        s /= 255;
        var i = (0 | (h / 60)) % 6,
          f = (h / 60) - i,
          p = v * (1 - s),
          q = v * (1 - f * s),
          t = v * (1 - (1 - f) * s);
        switch (i) {
          case 0:
            r = v;
            g = t;
            b = p;
            break;
          case 1:
            r = q;
            g = v;
            b = p;
            break;
          case 2:
            r = p;
            g = v;
            b = t;
            break;
          case 3:
            r = p;
            g = q;
            b = v;
            break;
          case 4:
            r = t;
            g = p;
            b = v;
            break;
          case 5:
            r = v;
            g = p;
            b = q;
            break;
        }
        return [0 | r, 0 | g, 0 | b];
      }

      // 図形のサイズ
      var size = 10;
      // 円を書く関数
      me.ellipse = function(rad) {
        console.log(rad);
        ctx.beginPath();
        ctx.moveTo(size / 2, size / 2);
        ctx.arc(size / 2, size / 2, rad, 0, 2 * Math.PI);
        ctx.fill();
      }

      // 四角形の描画
      me.rect = function(l) {
        ctx.fillRect(0, 0, l * 2, l * 2);
      }



      // 落ちた場所の情報を色相に変換する関数
      me.course2hue = function() {
        switch (me.course) {
          case 'c':
            return 0; // 赤
          case 'b':
            return 120; // 緑
          case 'w':
            return 240; // 青
        }
      }


      // ボールの速さの情報を明度に変換する関数
      me.speed2value = function() {
        if (me.speed == '') return 200;
        return (me.speed - 100) * 255 / 128;
      }

      //ボールの速さを円の大きさに変える変数
      me.speed2rad = function() {
        if (me.speed == '') return 4; // スピードがなければ中央値4を返す
        if (me.speed < 100) return 1; // スピードが１００以下なら外れ値と判断して１にする
        if (me.speed > 228) return 6; // スピードが228以なら外れ値と判断して１にする
        return (me.speed - 100) * 4 / 128 + 2; // スピードを100~228m/sの範囲から2~6の範囲にする
      }


      // ラリーの情報を描画する関数
      me.draw = function(ctx) {

        var hue = me.course2hue(); // 落ちた場所の情報を色相に変換
        var value = 200; // 明度、固定
        var saturation = 200; // 彩度、固定
        var color = me.hsv2rgb(hue, saturation, value);

        ctx.fillStyle = 'rgb(' + color[0] + ',' + color[1] + ',' + color[2] + ')'; // 色を指定
        // if(me.player == "圭") me.ellipse(me.speed2rad());
        // else me.rect(me.speed2rad());
        me.ellipse(me.speed2rad());
      }
    }




    //CSVファイルを読み込む

    // 読み込んだCSVデータを二次元配列に変換する関数convertCSVtoArray()の定義
    function convertCSVtoArray(str) { // 読み込んだCSVデータが文字列として渡される
      var result = []; // 最終的な二次元配列を入れるための配列
      var tmp = str.split("\n"); // 改行を区切り文字として行を要素とした配列を生成

      // 各行ごとにカンマで区切った文字列を要素とした二次元配列を生成
      for (var i = 0; i < tmp.length; ++i) {
        result[i] = tmp[i].split(',');
      }
      return result;
    }



    //CSVファイルを読み込む
    var req = new XMLHttpRequest(); // HTTPでファイルを読み込むためのXMLHttpRrequestオブジェクトを生成
    req.open("get", <?php echo '"' . $_GET['file_name'] . '"'; ?>, true); // アクセスするファイルを指定
    req.send(); // HTTPリクエストの発行



    // レスポンスが返ってきたらconvertCSVtoArray()を呼ぶ
    req.onload = function() {
      // ここに処理を描く
      if (req.status != 200) {
        document.write("XMLHttpRequestエラー :ファイル取得に失敗");
      }

      // 読み込んだＣＳＶファイル配列で取得
      data = convertCSVtoArray(req.responseText);

      // セット・ゲームのindex
      var games_x = 0;
      var games_y = 0;
      var sets_x = 0;
      var sets_y = 0;
      var set_flag = 0;

      // セット数の状況
      var set_max = data[0][0]; //セット数をあとから入れてる
      var game_max = 7;
      var point_max = 5;

      var sets = new Array(set_max);
      for (var i = 0; i < set_max; i++) {
        sets[i] = new Array(set_max);
        for (var j = 0; j < set_max; j++) {
          sets[i][j] = new Array(game_max);
          for (var k = 0; k < game_max; k++) {
            sets[i][j][k] = new Array(game_max);
            for (var l = 0; l < game_max; l++) {
              sets[i][j][k][l] = new Array(point_max);
              for (var m = 0; m < point_max; m++) {
                sets[i][j][k][l][m] = new Array(point_max);
                for (var n = 0; n < point_max; n++) {
                  sets[i][j][k][l][m][n] = 0; // 初期化
                }
              }
            }
          }
        }
      }

      console.log(sets);

      target = document.getElementById("match");
      target.innerHTML = "vs " + data[1][3];

      // data配列をデータごとの配列に入れなおす
      for (var i = 1; i < data.length; i++) {
        WinLose[i] = data[i][7];
        // TotalGame[i] = data[i][5];
        // Set[i] = data[i][4];
        FirstSecond[i] = data[i][8];
        course[i] = data[i][9];
        speed[i] = data[i][10];
        side[i] = data[i][14];
        player[i] = data[i][6];
        if (player[i] == '圭') {
          ScoreServer[i] = data[i][12];
          ScoreReturner[i] = data[i][13];
        } else {
          ScoreServer[i] = data[i][13];
          ScoreReturner[i] = data[i][12];
        }



        // console.log(ScoreServer[i]);

        if (ScoreServer[i] == 0 && ScoreReturner[i] == 0 && i != 1) {
          // games[games_x][games_y]=points;
          // for(var n = 0; n < points.length; n++){
          //   for(var m = 0; m < points.length; m++){
          //     points[n][m] = 0;
          //   }
          // }
          if (ScoreServer[i - 1] == "Ad") {
            if (player[i - 1] == "圭") {
              games_x++;
            } else {
              games_y++;
            }
          } else if (ScoreReturner[i - 1] == "Ad") {
            if (player[i - 1] == "圭") {
              games_y++;
            } else {
              games_x++;
            }
          } else if (ScoreServer[i - 1] == "40") {
            if (player[i - 1] == "圭") {
              games_x++;
            } else {
              games_y++;
            }
          } else {
            if (player[i - 1] == "圭") {
              games_y++;
            } else {
              games_x++;
            }
          }
          // console.log(ScoreServer[i-1]);
        }


        if (games_x == 6 && games_y < 5) {
          // // sets[sets_x][sets_y] = games;
          sets_x++;
          games_x = 0;
          games_y = 0;
          set_flag++;
          // games_reset();
        } else if (games_y == 6 && games_x < 5) {
          // sets[sets_x][sets_y] = games;
          sets_y++;
          games_x = 0;
          games_y = 0;
          set_flag++;
          // games_reset();
        } else if (games_x == 7) {
          // sets[sets_x][sets_y] = games;
          sets_x++;
          games_x = 0;
          games_y = 0;
          set_flag++;
          // games_reset();
        } else if (games_y == 7) {
          // sets[sets_x][sets_y] = games;
          sets_y++;
          games_x = 0;
          games_y = 0;
          set_flag++;
          // games_reset();
        }
        // console.log(sets_x, sets_y, games_x, games_y)

        if (player[i] == '圭') {

          switch (ScoreServer[i]) {
            case "0":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][0][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][0][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][0][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][0][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;

              }
              break;
            case "15":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][1][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][1][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][1][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][1][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
              }
              break;
            case "30":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][2][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][2][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][2][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][2][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
              }
              break;
            case "40":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][3][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][3][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][3][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][3][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "Ad":
                  sets[sets_x][sets_y][games_x][games_y][3][4] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
              }
              break;
            case "Ad":
              sets[sets_x][sets_y][games_x][games_y][4][3] = new contents(side[i], course[i], speed[i], player[i]);
              break;
          }
        } else {
          switch (ScoreServer[i]) {
            case "0":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][0][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][1][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][2][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][3][0] = new contents(side[i], course[i], speed[i], player[i]);
                  break;

              }
              break;
            case "15":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][0][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][1][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][2][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][3][1] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
              }
              break;
            case "30":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][0][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][1][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][2][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][3][2] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
              }
              break;
            case "40":
              switch (ScoreReturner[i]) {
                case "0":
                  sets[sets_x][sets_y][games_x][games_y][0][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "15":
                  sets[sets_x][sets_y][games_x][games_y][1][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "30":
                  sets[sets_x][sets_y][games_x][games_y][2][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "40":
                  sets[sets_x][sets_y][games_x][games_y][3][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
                case "Ad":
                  sets[sets_x][sets_y][games_x][games_y][4][3] = new contents(side[i], course[i], speed[i], player[i]);
                  break;
              }
              break;
            case "Ad":
              sets[sets_x][sets_y][games_x][games_y][3][4] = new contents(side[i], course[i], speed[i], player[i]);
              break;
          }
        }

      }
      // console.log(sets);

      // sets[0][0][0][0][0][0].draw(ctx);
      draw(scale, sets);
    }

    function draw(scale, sets) {
      ctx.scale(scale, scale);
      var point_size = 10;
      var point_margin = 2;
      cvs.width = ((point_size + point_margin) * 5.5 * 7 + 10) * set_max;
      cvs.height = ((point_size + point_margin) * 5.5 * 7 + 10) * set_max;

      for (var a = 0; a < set_max; a++) {
        for (var b = 0; b < set_max; b++) {
          ctx.save();
          ctx.translate(((point_size + point_margin) * 5.5 * 7 + 10) * a, ((point_size + point_margin) * 5.5 * 7 + 10) * b);
          for (var c = 0; c < game_max; c++) {
            for (var d = 0; d < game_max; d++) {
              ctx.save();
              ctx.translate(((point_size + point_margin) * 5.5) * c, ((point_size + point_margin) * 5.5) * d);

              // 1game
              for (var i = 0; i < point_max; i++) {
                for (var j = 0; j < point_max; j++) {
                  ctx.save();
                  ctx.translate((point_size + point_margin) * i, (point_size + point_margin) * j);


                  // 背景色の指定
                  if (sets[a][b][c][d][0][0] != 0) {
                    // サーバーが圭なら青
                    if (sets[a][b][c][d][0][0].player == "圭") ctx.fillStyle = '#d9e9f9';
                    // サーバーが圭以外なら黄色
                    else ctx.fillStyle = '#f9efae';
                  } else {
                    // それ以外はグレー
                    ctx.fillStyle = '#efefef';
                  }
                  // 背景の描画
                  ctx.fillRect(0, 0, point_size, point_size);

                  // サーブの情報の描画
                  if (sets[a][b][c][d][i][j] != 0) {
                    sets[a][b][c][d][i][j].draw(ctx);
                  }
                  ctx.restore();
                }
              }
              // -------

              ctx.restore();

            }
          }
          ctx.restore();
        }
      }
    }


    document.getElementById('scale_slider').addEventListener('input', function(e) {
      var scale = e.value / 10;
      document.getElementById('scale_slider_value').textContent = scale;
      draw(scale);
    });
  </script>
  <!-- <div id="chartContainer" style="height:70%; width:70%">
  <canvas id="canvas2"> </canvas>
</div>
<script src="Chartjavascript.js"></script> -->
</body>

</html>